<?php

namespace Xi\Bundle\AjaxBundle\Tests\Form\Type;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilder;

/**
 * @author Mikko Hirvonen <mikko.petteri.hirvonen@gmail.com>
 */
class TestUserInfoFormType extends AbstractType
{
    /**
     * @param FormBuilder $builder
     * @param array $options
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('address');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'test_user_info_form_type';
    }

    /**
     * @param array $options
     * @return array
     */
    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Xi\Bundle\AjaxBundle\Tests\Model\TestUserInfo',
        );
    }
}
