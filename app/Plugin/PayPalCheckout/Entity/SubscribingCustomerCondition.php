<?php

namespace Plugin\PayPalCheckout\Entity;

/**
 * Class SubscribingCustomerCondition
 * @package Plugin\PayPalCheckout\Entity
 */
class SubscribingCustomerCondition
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $price_courses;

    /**
     * @var bool
     */
    private $is_deleted;

    /**
     * @var bool
     */
    private $is_failed;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getPriceCourses()
    {
        return $this->price_courses;
    }

    /**
     * @param $price_courses
     * @return $this
     */
    public function setPriceCourses($price_courses)
    {
        $this->price_courses = $price_courses;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsDeleted()
    {
        return $this->is_deleted;
    }

    /**
     * @param $is_deleted
     * @return $this
     */
    public function setIsDeleted($is_deleted)
    {
        $this->is_deleted = $is_deleted;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsFailed()
    {
        return $this->is_failed;
    }

    /**
     * @param $is_failed
     * @return $this
     */
    public function setIsFailed($is_failed)
    {
        $this->is_failed = $is_failed;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'price_courses' => $this->getPriceCourses(),
            'is_deleted' => $this->getIsDeleted(),
            'is_failed' => $this->getIsFailed(),
        ];
    }
}
