<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 07/01/2017
 * Time: 18:31
 */

namespace BLOG\Models;


class Comment
{
    protected $id;
    protected $post;
    protected $date;
    protected $username;
    protected $content;

    /**
     * Comment constructor.
     * @param $post
     * @param $date
     * @param $username
     * @param $content
     */
    public function __construct($post, $username, $content)
    {
        $this->post = $post;
        $this->date = new \DateTime('now');
        $this->username = $username;
        $this->content = $content;
    }


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
    public function getPost()
    {
        return $this->post;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
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
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }


}