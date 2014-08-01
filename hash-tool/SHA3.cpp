#include <cstring>
#include <iostream>
#include "SHA3.h"

#define bzero(b,len) (memset((b), '\0', (len)), (void) 0)

// Circular rotate left
#define ROT_L( X, Y ) (( X << Y ) | ( X >> (64 - Y) ))
#define ROUNDS 24

/// For converting binary output to hexidecimal for printing
const char *hexLookup = "0123456789abcdef";

const keccakLane_t roundConstants[] = {
    0x0000000000000001,
    0x0000000000008082,
    0x800000000000808A,
    0x8000000080008000,
    0x000000000000808B,
    0x0000000080000001,
    0x8000000080008081,
    0x8000000000008009,
    0x000000000000008A,
    0x0000000000000088,
    0x0000000080008009,
    0x000000008000000A,
    0x000000008000808B,
    0x800000000000008B,
    0x8000000000008089,
    0x8000000000008003,
    0x8000000000008002,
    0x8000000000000080,
    0x000000000000800A,
    0x800000008000000A,
    0x8000000080008081,
    0x8000000000008080,
    0x0000000080000001,
    0x8000000080008008
};

SHA3::SHA3( int digestSize ) : _digestSize( digestSize ){
    // zero the state
    // CHANGE: Now uses bit shifting instead of multiplication
    _spongeCapacity = _digestSize << 4;
    _spongeRate = 1600 - _spongeCapacity;
    _messageBuffer = new unsigned char[_spongeRate];
    _reset();
}

SHA3::~SHA3(){
    // CHANGE: Deconstructor included
    delete[] _messageBuffer;
}
////////// Accessors //////////

int SHA3::digestSize(){
    return _digestSize;
}

////////// Ingesting Data //////////

void SHA3::hash( const int b ){
    _bufferLocation[0] = (unsigned char)b;
    _bufferLocation++;
    if( _bufferLocation == &_messageBuffer[_spongeRate>>3] ){
        _bufferLocation = _messageBuffer;
        _absorbBuffer();
    }
}

void SHA3::hashString( const char *str ){
    int byte = 0;
    while( str[byte] != '\0' ){
        hash( (int)( (unsigned char) str[byte] ) );
        byte++;
    }
}

void SHA3::hashHexString( const char *str ){
    int byte = 0;
    while( str[byte] != '\0' ){
        int f = str[byte];
        int s = str[byte+1];
        if( f >= 97 ) f -= 87; // lowercase
        else if( f >= 65 ) f -= 55; // uppercase
        else f -= 48; // numeric

        if( s >= 97 ) s -= 87; // lowercase
        else if( s >= 65 ) s -= 55; // uppercase
        else s -= 48; // numeric

        hash( (f << 4) | s );
        byte+=2;
    }
}

////////// Expelling Data //////////

void SHA3::digest( unsigned char d[] ){
    // Pad with 10*1 padding
    _bufferLocation[0] = 1;
    _bufferLocation++;
    // CHANGE: Uses system bzero function instead of while loop to initilize
    bzero( _bufferLocation, &_messageBuffer[_spongeRate>>3] - _bufferLocation );
    _messageBuffer[(_spongeRate >> 3) - 1] |= 0x80;
    _absorbBuffer();

    // Squeeze
    memcpy( d, _state, digestSize() );
    _reset(); // Ready the function to hash another message
}

char *SHA3::digestInHex(){
    unsigned char *bytes = new unsigned char[ digestSize() ];
    char *hex = new char[ (digestSize() << 1) + 1 ];

    // CHANGE: Uses bitshifting instead of multiplication
    hex[digestSize() << 1] = '\0';
    digest( bytes );

    for( int byte = 0; byte < digestSize(); byte++ ){
        // CHANGE: Uses bitshifting instead of multiplication
        hex[byte << 1]   = hexLookup[bytes[byte] >> 4];
        hex[(byte << 1)+1] = hexLookup[bytes[byte] & 15];
    }
    delete[] bytes;
    return hex;
}

////////// Internals //////////

inline void SHA3::_reset(){
    // CHANGE: Uses system bzero function instead of while loop to initilize
    bzero( _state, 200 ); //25 64-byte lanes
    _bufferLocation = _messageBuffer;
}

void SHA3::_absorbBuffer(){
    keccakLane_t *x = (keccakLane_t *)_messageBuffer;
    for( int i = 0; i*64 < _spongeRate; i++ ){
        _state[i/5][i%5] ^= x[i]; // TODO: unroll
    }
    _performRounds( ROUNDS );
}

// CHANGE: Function changed to inline
inline void SHA3::_performRounds( int rounds ){
    keccakLane_t b[5][5];
    keccakLane_t c[5];
    keccakLane_t d[5];

    for( int i = 0; i < rounds; i++ ){

        //CHANGE: For loops change to pre-determined steps, reduces branching

        // Theta step
        c[0] = _state[0][0] ^ _state[1][0] ^ _state[2][0] ^ _state[3][0] ^ _state[4][0];
        c[1] = _state[0][1] ^ _state[1][1] ^ _state[2][1] ^ _state[3][1] ^ _state[4][1];
        c[2] = _state[0][2] ^ _state[1][2] ^ _state[2][2] ^ _state[3][2] ^ _state[4][2];
        c[3] = _state[0][3] ^ _state[1][3] ^ _state[2][3] ^ _state[3][3] ^ _state[4][3];
        c[4] = _state[0][4] ^ _state[1][4] ^ _state[2][4] ^ _state[3][4] ^ _state[4][4];

        d[0] = c[4] ^ ROT_L( c[1], 1 );
        d[1] = c[0] ^ ROT_L( c[2], 1 );
        d[2] = c[1] ^ ROT_L( c[3], 1 );
        d[3] = c[2] ^ ROT_L( c[4], 1 );
        d[4] = c[3] ^ ROT_L( c[0], 1 );

        _state[0][0] ^= d[0];
        _state[0][1] ^= d[1];
        _state[0][2] ^= d[2];
        _state[0][3] ^= d[3];
        _state[0][4] ^= d[4];
        _state[1][0] ^= d[0];
        _state[1][1] ^= d[1];
        _state[1][2] ^= d[2];
        _state[1][3] ^= d[3];
        _state[1][4] ^= d[4];
        _state[2][0] ^= d[0];
        _state[2][1] ^= d[1];
        _state[2][2] ^= d[2];
        _state[2][3] ^= d[3];
        _state[2][4] ^= d[4];
        _state[3][0] ^= d[0];
        _state[3][1] ^= d[1];
        _state[3][2] ^= d[2];
        _state[3][3] ^= d[3];
        _state[3][4] ^= d[4];
        _state[4][0] ^= d[0];
        _state[4][1] ^= d[1];
        _state[4][2] ^= d[2];
        _state[4][3] ^= d[3];
        _state[4][4] ^= d[4];

        // Rho and Pi steps
        b[0][0] = _state[0][0]; // rotate left by 0 bits
        b[1][3] = ROT_L( _state[1][0], 36 );
        b[2][1] = ROT_L( _state[2][0], 3 );
        b[3][4] = ROT_L( _state[3][0], 41 );
        b[4][2] = ROT_L( _state[4][0], 18 );

        b[0][2] = ROT_L( _state[0][1], 1 );
        b[1][0] = ROT_L( _state[1][1], 44 );
        b[2][3] = ROT_L( _state[2][1], 10 );
        b[3][1] = ROT_L( _state[3][1], 45 );
        b[4][4] = ROT_L( _state[4][1], 2 );

        b[0][4] = ROT_L( _state[0][2], 62 );
        b[1][2] = ROT_L( _state[1][2], 6 );
        b[2][0] = ROT_L( _state[2][2], 43 );
        b[3][3] = ROT_L( _state[3][2], 15 );
        b[4][1] = ROT_L( _state[4][2], 61 );

        b[0][1] = ROT_L( _state[0][3], 28 );
        b[1][4] = ROT_L( _state[1][3], 55 );
        b[2][2] = ROT_L( _state[2][3], 25 );
        b[3][0] = ROT_L( _state[3][3], 21 );
        b[4][3] = ROT_L( _state[4][3], 56 );

        b[0][3] = ROT_L( _state[0][4], 27 );
        b[1][1] = ROT_L( _state[1][4], 20 );
        b[2][4] = ROT_L( _state[2][4], 39 );
        b[3][2] = ROT_L( _state[3][4], 8 );
        b[4][0] = ROT_L( _state[4][4], 14 );

        // Chi step
        _state[0][0] = b[0][0] ^ ((~b[1][0]) & b[2][0]);
        _state[1][0] = b[0][1] ^ ((~b[1][1]) & b[2][1]);
        _state[2][0] = b[0][2] ^ ((~b[1][2]) & b[2][2]);
        _state[3][0] = b[0][3] ^ ((~b[1][3]) & b[2][3]);
        _state[4][0] = b[0][4] ^ ((~b[1][4]) & b[2][4]);

        _state[0][1] = b[1][0] ^ ((~b[2][0]) & b[3][0]);
        _state[1][1] = b[1][1] ^ ((~b[2][1]) & b[3][1]);
        _state[2][1] = b[1][2] ^ ((~b[2][2]) & b[3][2]);
        _state[3][1] = b[1][3] ^ ((~b[2][3]) & b[3][3]);
        _state[4][1] = b[1][4] ^ ((~b[2][4]) & b[3][4]);

        _state[0][2] = b[2][0] ^ ((~b[3][0]) & b[4][0]);
        _state[1][2] = b[2][1] ^ ((~b[3][1]) & b[4][1]);
        _state[2][2] = b[2][2] ^ ((~b[3][2]) & b[4][2]);
        _state[3][2] = b[2][3] ^ ((~b[3][3]) & b[4][3]);
        _state[4][2] = b[2][4] ^ ((~b[3][4]) & b[4][4]);

        _state[0][3] = b[3][0] ^ ((~b[4][0]) & b[0][0]);
        _state[1][3] = b[3][1] ^ ((~b[4][1]) & b[0][1]);
        _state[2][3] = b[3][2] ^ ((~b[4][2]) & b[0][2]);
        _state[3][3] = b[3][3] ^ ((~b[4][3]) & b[0][3]);
        _state[4][3] = b[3][4] ^ ((~b[4][4]) & b[0][4]);

        _state[0][4] = b[4][0] ^ ((~b[0][0]) & b[1][0]);
        _state[1][4] = b[4][1] ^ ((~b[0][1]) & b[1][1]);
        _state[2][4] = b[4][2] ^ ((~b[0][2]) & b[1][2]);
        _state[3][4] = b[4][3] ^ ((~b[0][3]) & b[1][3]);
        _state[4][4] = b[4][4] ^ ((~b[0][4]) & b[1][4]);

        // Iota step
        _state[0][0] ^= roundConstants[i];
    }
}

////////// Debugging Functions //////////

void SHA3::_printMessageBuffer(){
    std::cout << "mb = [ ";
    for( int i = 0; i < _spongeRate/8; i++ ){
        std::cout << (int)_messageBuffer[i] << " ";
    }
    std::cout << "]" << std::endl;
}

void SHA3::_printSponge(){
    std::cout << "s = [ " << std::hex;
    for( int x = 0; x < 5; x++ ){
        for( int y = 0; y < 5; y++ ){
            std::cout << _state[x][y] << " ";
        }
    }
    std::cout << std::dec << "]" << std::endl;
}
