<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 07/01/2017
 * Time: 10:37
 */

namespace BLOG\Models;


use Doctrine\Common\Collections\ArrayCollection;

class Post
{
    protected $id;
    protected $user;
    protected $title;
    protected $date;
    protected $content;
    protected $comments;
    protected $isadraft;

    /**
     * Post constructor.
     * @param $user
     * @param $title
     */
    public function __construct($title, $content, $user, $isadraft)
    {
        $this->title = $title;
        $this->content = $content;
        $this->date = new \DateTime('now');
        $this->user = $user;
        $this->isadraft = $isadraft;
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
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
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
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return mixed
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @param mixed $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    /**
     * @return mixed
     */
    public function getIsadraft()
    {
        return $this->isadraft;
    }

    /**
     * @param mixed $isadraft
     */
    public function setIsadraft($isadraft)
    {
        $this->isadraft = $isadraft;
    }


}