<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 07/01/2017
 * Time: 10:23
 */

namespace BLOG\Models;


class User
{
    protected $id;
    protected $username;
    protected $password;
    protected $posts;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getPosts()
    {
        return $this->posts;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }


}