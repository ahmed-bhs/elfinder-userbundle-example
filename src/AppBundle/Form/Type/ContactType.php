<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ContactType
 * @package AppBundle\Form\Type
 */
class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('content', 'ckeditor')
            ->getForm();

    }

    public function getName()
    {
        return 'contact';
    }
}