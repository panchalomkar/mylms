<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details.
 *
 * @package    local_sitesync
 * @copyright  2023 WisdmLabs <support@wisdmlabs.com>
 * @author     Gourav G <support@wisdmlabs.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sitesync;

defined('MOODLE_INTERNAL') || die();

class JsonEncryptor {
    private $publickey;
    private $privatekey;
    private $keySize = 2048;
    private $expirationat;

    // Constructor to generate keys upon class instantiation
    public function __construct() {
        // Set existing keys if any.
        $this->getExistingKeys();
    }

    // Getter for the public key
    public function getPublickey() {
        return $this->publickey;
    }

    // Getter for the private key
    public function getPrivatekey() {
        return $this->privatekey;
    }

    // Getter for the private key
    public function getExpirationAt() {
        return $this->expirationat;
    }
    // Function to generate public and private keys
    public function generateKeys() {
        $config = [
            "digest_alg" => "sha256",
            "private_key_bits" => $this->keySize,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        // Create a new key pair
        $resource = openssl_pkey_new($config);

        // Extract the private key
        openssl_pkey_export($resource, $privatekey);

        // Extract the public key
        $publickey = openssl_pkey_get_details($resource)["key"];

        // Save keys to database
        $record = new \stdClass();
        $record->privatekey = $privatekey;
        $record->publickey = $publickey;
        $record->expirationat = time() + (10*60); // Adding 10*60 into epoch time will increase the time by 10 minutes.

        set_config('secure_keys', json_encode($record), 'local_sitesync');

        $this->privatekey = $privatekey;
        $this->publickey = $publickey;
        $this->expirationat = $record->expirationat;
    }

    // Get existing keys
    public function getExistingKeys() {
        $securekeys = get_config('local_sitesync', 'secure_keys');

        if (!$securekeys) {
            return;
        }

        $securekeys = json_decode($securekeys);

        // Set keys only when they are not expired.
        if ($securekeys->expirationat >= time()) {
            $this->privatekey = $securekeys->privatekey;
            $this->publickey = $securekeys->publickey;
            $this->expirationat = $securekeys->expirationat;
        }
    }

    // Function to encrypt JSON data using the public key
    public function encryptJson($jsonData, $publickey = false) {

        $jsonData = json_encode($jsonData);

        $encrypted = '';
        $jsonString = json_encode($jsonData);

        // Encrypt the JSON data with the public key
        if(!$publickey){
            openssl_public_encrypt($jsonString, $encrypted, $this->publickey);
        }else{
            openssl_public_encrypt($jsonString, $encrypted, $publickey);
        }

        return base64_encode($encrypted);
    }

    // Function to decrypt JSON data using the private key
    public function decryptJson($encryptedData) {
        $decrypted = '';
        $encryptedData = base64_decode($encryptedData);

        // Decrypt the data with the private key
        openssl_private_decrypt($encryptedData, $decrypted, $this->privatekey);

        return json_decode($decrypted, true);
    }

    // Return true/false based on the available public key.
    public function isMasterPublicKeyAvailable() {
        $publickey = get_config('local_sitesync', 'master_secret_pub_key');
        if (!$publickey) {
            return false;
        }

        $parts = $this->separatePublicnEpoch($publickey);

        $publickey = $parts['publicKey'];
        $epoch = $parts['epoch'];

        // Checkng if the public key is expired. checking 1 min before the expiration time.
        if ($epoch < time()-60) {
            return false;
        }

        return $publickey;
    }

    // Separate the public key and the epoch time.
    public function separatePublicnEpoch($key) {
        // Separate the last 10 characters (epoch time)
        $epoch = substr($key, -10);

        // Extract the public key by removing the last 10 characters
        $publicKey = substr($key, 0, -10);

        // Return both parts as an array
        return [
            'publicKey' => $publicKey,
            'epoch' => $epoch
        ];
    }
}
