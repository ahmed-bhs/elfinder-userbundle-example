<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Form\Type\ContactType;

/**
 * Class AppController
 * @package AppBundle\Controller
 */
class AppController extends Controller
{
    /**
     * @Template()
     */
    public function indexAction()
    {
        $form = $this->createForm(new ContactType());
        return array('form'=>$form->createView());
    }
}
