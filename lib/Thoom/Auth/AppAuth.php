<?php
/**
 * SimpleAuth Class
 *
 * @author Z.d. Peacock <zdpeacock@thoomtech.com>
 * @copyright (c) 2011 Thoom Technologies LLC
 */

namespace Thoom\Auth;


class AppAuth
{
    protected $token;
    protected $secret;

    public function __construct($token, $secret){
        $this->setToken($token);
        $this->setSecret($secret);
    }

    public function setToken($token){
        $this->token = $token;
    }

    public function setSecret($secret) {
        $this->secret = $secret;
    }

    public function validate($nonce, $signature){
        if ($nonce < time() - 3600 || $nonce > time() + 3600)
            throw new \OutOfRangeException("Nonce received is out of range");

        return sha1($this->token.$this->secret.$nonce) == $signature;
    }

}
