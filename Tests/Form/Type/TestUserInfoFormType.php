<?php

namespace Xi\Bundle\AjaxBundle\Tests\Form\Type;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @author Mikko Hirvonen <mikko.petteri.hirvonen@gmail.com>
 */
class TestUserInfoFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
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
     * @param OptionsResolverInterface $resolver
     * @return array
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Xi\Bundle\AjaxBundle\Tests\Model\TestUserInfo',
        ));
    }
}
