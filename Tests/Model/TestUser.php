<?php

namespace Xi\Bundle\AjaxBundle\Tests\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author Mikko Hirvonen <mikko.hirvonen@soprano.fi>
 */
class TestUser
{
    /**
     * @Assert\NotBlank()
     * @var string
     */
    public $name;

    /**
     * @var TestUserInfo
     */
    public $userInfo;
}
