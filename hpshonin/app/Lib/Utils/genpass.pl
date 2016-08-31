# MT用のパスワード生成
use Digest::SHA;
print &set_password($ARGV[0]);

sub set_password {
    my ($pass) = @_;
    my @alpha  = ( 'a' .. 'z', 'A' .. 'Z', 0 .. 9 );
    my $salt   = join '', map $alpha[ rand @alpha ], 1 .. 16;
    my $crypt_sha;

    # Can use SHA512
    $crypt_sha
        = '$6$' 
        . $salt . '$'
        . Digest::SHA::sha512_base64( $salt . $pass );
    return $crypt_sha;
}
