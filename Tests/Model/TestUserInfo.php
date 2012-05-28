<?php

namespace Xi\Bundle\AjaxBundle\Tests\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author Mikko Hirvonen <mikko.petteri.hirvonen@gmail.com>
 */
class TestUserInfo
{
    /**
     * @Assert\NotBlank()
     * @var string
     */
    public $address;
}
