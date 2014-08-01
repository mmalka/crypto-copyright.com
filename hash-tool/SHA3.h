#ifndef SHA3_H
#define SHA3_H

#include "HashFunction.h"

/// SHA-3 winning hash algorithm Keccak
///
/// @author: Christopher Bentivenga
/// @author: Frederick Christie
/// @author: Michael Kitson

typedef unsigned long long keccakLane_t;

class SHA3 : public HashFunction{
 public:
    SHA3( int digestSize );
    ~SHA3();

    /// Adds an entire string to the message
    ///
    /// @param  string  The string of bytes to add
    void hashString( const char *str );

    /// Adds an entire hexidecimal string to the message
    ///
    /// @param  string  The hex string of bytes to add
    void hashHexString( const char *str );

    /// Returns a representation of the digest as a hexidecimal string
    ///
    /// @return The hex string, ownership of which is given to the caller
    char *digestInHex();

    // Overridden functions from HashFunction
    int digestSize();
    void hash( const int b );
    void digest( unsigned char d[] );

 private:
    int _digestSize; // bytes

    // Round state
    keccakLane_t _state[5][5];

    // Digest-length specific Values
    int _spongeCapacity;
    int _spongeRate;

    unsigned char *_messageBuffer;  // rate bits wide, defined during construction
    unsigned char *_bufferLocation; // used for writing and to know when to flush the buffer

    void _reset();
    void _performRounds( int rounds );
    void _absorbBuffer();

    // Debugging
    void _printMessageBuffer();
    void _printSponge();
};

#endif
