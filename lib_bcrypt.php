<?php

/*
 * lib_bcrypt - bcrypt hashing in PHP
 * 
 *  Based on Portable PHP password hashing framework: http://www.openwall.com/phpass/
 * 
 */

class BCryptHasher {

    /**
     * Random state
     * @var string 
     */
    private $random_state;

    /**
     * Construction method. Check if there is Blowfish support.
     * @throws Exception 
     */
    public function __construct() {
        if (!CRYPT_BLOWFISH)
            throw new Exception("lib_bcrypt requires CRYPT_BLOWFISH PHP support");

        $this->random_state = microtime();
        $this->random_state .= uniqid('', true);
    }

    /**
     * Generate random bytes
     * @param int $count Number of bytes
     * @return string Random bytes 
     */
    private function get_random_bytes($count) {

        /*
         * Attempt generate random bytes
         */
        if (function_exists('openssl_random_pseudo_bytes')) {
            $output = openssl_random_pseudo_bytes($count);
        } else {
            if (is_readable('/dev/urandom')) {
                if ($fh = @fopen('/dev/urandom', 'rb')) {
                    $output = fread($fh, $count);
                    fclose($fh);
                }
            } else {
                /*
                 * If OpenSSL isn't present and neither is /dev/urandom, provide a fallback method.
                 */
                $output = bin2hex(mcrypt_create_iv($count, MCRYPT_DEV_URANDOM));
            }
        }
        if (strlen($output) < $count) {
            $output = '';
            for ($i = 0; $i < $count; $i += 16) {
                $this->random_state =
                        sha1(microtime() . $this->random_state);
                $output .=
                        pack('H*', sha1($this->random_state));
            }
            $output = substr($output, 0, $count);
        }

        return $output;
    }

    /**
     * Generate hash salt
     * @param string $input Salt input
     * @param int $work_factor Work factor
     * @return string 
     */
    private function gensalt_blowfish($input, $work_factor) {

        /* This one needs to use a different order of characters and a
         * different encoding scheme from the one in encode64() in phpass.
         * We care because the last character in our encoded string will
         * only represent 2 bits.  While two known implementations of
         * bcrypt will happily accept and correct a salt string which
         * has the 4 unused bits set to non-zero, we do not want to take
         * chances and we also do not want to waste an additional byte
         * of entropy.
         */
        $itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $output = '$2a$';
        $output .= chr(ord('0') + $work_factor / 10);
        $output .= chr(ord('0') + $work_factor % 10);
        $output .= '$';

        $i = 0;
        do {
            $c1 = ord($input[$i++]);
            $output .= $itoa64[$c1 >> 2];
            $c1 = ($c1 & 0x03) << 4;
            if ($i >= 16) {
                $output .= $itoa64[$c1];
                break;
            }

            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 4;
            $output .= $itoa64[$c1];
            $c1 = ($c2 & 0x0f) << 2;

            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 6;
            $output .= $itoa64[$c1];
            $output .= $itoa64[$c2 & 0x3f];
        } while (1);

        return $output;
    }

    /**
     * Hash plaintext
     * @param string $password Text to be hashed
     * @param int $work_factor Work factor
     * @return mixed 
     */
    public function HashPassword($password, $work_factor = 8) {
        if ($work_factor < 4 || $work_factor > 31)
            $work_factor = 8;

        $random = $this->get_random_bytes(16);
        $salt = $this->gensalt_blowfish($random, $work_factor);
        $hash = crypt($password, $salt);
        if (strlen($hash) == 60)
            return $hash;
        return false;
    }

    /**
     * Check a password against a stored hash
     * @param string $password Plain text password
     * @param string $stored_hash Stored hash
     * @return boolean 
     */
    public function CheckPassword($password, $stored_hash) {
        $hash = crypt($password, $stored_hash);
        return $hash === $stored_hash;
    }

}

?>
