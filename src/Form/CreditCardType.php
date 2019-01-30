<?php
/**
 * @licence Proprietary
 */
namespace Jihel\VikingPayBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

/**
 * Class CreditCardType
 *
 * @author Joseph LEMOINE <j.lemoine@ludi.cat>
 */
class CreditCardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('holder', Type\TextType::class, [
                'label' => 'form.label.holder',
                'required' => false,
            ])
            ->add('number', Type\TextType::class, [
                'label' => 'form.label.number',
                'required' => false,
            ])
            ->add('expire_month', Type\ChoiceType::class, [
                'label' => 'form.label.expire_month',
                'required' => false,
                'empty_data' => null,
                'choices' => range(1, 12),
                'choice_value' => function ($val) {
                    return str_pad($val, 2, '0', STR_PAD_LEFT);
                },
                'choice_label' => function ($val) {
                    return str_pad($val, 2, '0', STR_PAD_LEFT);
                },
            ])
            ->add('expire_year', Type\ChoiceType::class, [
                'label' => 'form.label.expire_year',
                'required' => false,
                'empty_data' => null,
                'choices' => range(date('Y'), (date('Y') + 10)),
                'choice_label' => function ($val) {
                    return $val;
                },
            ])
            ->add('code', Type\TextType::class, [
                'label' => 'form.label.code',
                'required' => false,
            ])
        ;
    }

    /**
     * @param ExceptionInterface $resolver
     */
    public function setDefaultOptions(ExceptionInterface $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'JihelVikingPay',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'viking_pay_cc';
    }
}
