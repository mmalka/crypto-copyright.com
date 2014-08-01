#ifndef HASH_FUNCTION_H
#define HASH_FUNCTION_H

/// Specification for a generic hash function to which our implemtation must
/// conform. Adapted from HashFunction.java provided by Professor Alan Kaminsky
/// at: http://www.cs.rit.edu/~ark/482/ugradproject/java2html.php?file=3
///
/// @author: Christopher Bentivenga
/// @author: Frederick Christie
/// @author: Michael Kitson
/// @contributor: Alan Kaminsky

class HashFunction{
 public:
    /// Returns this function's digest size in bytes
    ///
    /// @return Digest size.
    virtual int digestSize() =0;

    /// Append the given byte to the message being hashed. Only the least
    /// significant 8 bits of b are used.
    ///
    /// @param  b  Message byte.
    virtual void hash( const int b ) =0;

    /// Obtain the message digest. The digest parameter must be an array of
    /// bytes whose length is equal to digestSize(). The message consists of the
    /// series of bytes provided to the hash() method. The digest of the
    /// message is stored in the digest array.
    ///
    /// @param  digest  Message digest (output).
    virtual void digest( unsigned char d[] ) =0;
};

#endif
