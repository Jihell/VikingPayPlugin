<?php
/**
 * @licence Proprietary
 */
namespace Jihel\VikingPayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Class MonthType
 *
 * @author Joseph LEMOINE <j.lemoine@ludi.cat>
 */
class MonthType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove('day')
            ->addModelTransformer(new CallbackTransformer(
                function ($dateTime) {
                    return empty($dateTime) ? null : $dateTime->format('Y-m');
                },
                function ($monthString) {
                    return new \DateTime(sprintf('%s-01', $monthString));
                }
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['type'] = 'month';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return DateType::class;
    }
}
