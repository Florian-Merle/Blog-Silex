<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 06/01/2017
 * Time: 21:16
 */

namespace BLOG\Models;


class ContactMessage
{
    protected $id;
    protected $title;
    protected $date;
    protected $content;
    protected $mail;
    protected $viewed;

    /**
     * ContactMessage constructor.
     * @param $id
     * @param $title
     * @param $date
     * @param $content
     * @param $mail
     */
    public function __construct($title, $content, $mail)
    {
        $this->title = $title;
        $this->date = new \DateTime('now');
        $this->content = $content;
        $this->mail = $mail;
        $this->viewed = false;
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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return \DateTime
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
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * @return mixed
     */
    public function getViewed()
    {
        return $this->viewed;
    }

    /**
     * @param mixed $viewed
     */
    public function setViewed($viewed)
    {
        $this->viewed = $viewed;
    }
}