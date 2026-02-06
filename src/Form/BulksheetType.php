<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BulksheetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('asin', TextType::class, [
                'label' => 'ASIN',
                'attr' => ['readonly' => true, 'class' => 'form-control-plaintext'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex(pattern: '/^[A-Z0-9]{10}$/', message: 'ASIN invalide')
                ]
            ])
            ->add('sku', TextType::class, [
                'label' => 'Si vous êtes vendeur, vous devez renseigner un SKU.',
                'attr' => ['readonly' => true, 'class' => 'form-control-plaintext'],
            ])
            ->add('campaignId', TextType::class, [
                'label' => 'ID Campagne',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 100)
                ]
            ])
            ->add('autobid', NumberType::class, [
                'label' => 'Enchère Auto',
                'attr' => ['step' => '0.01', 'placeholder' => '0.35', 'class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                    new Assert\Range(min: 0.02, max: 100)
                ]
            ])
            ->add('keywords', CollectionType::class, [
                'entry_type' => KeywordType::class,
                'allow_add' => false,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'prototype' => true,
                'prototype_name' => '__name__'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Créer le bulksheet',
                'attr' => ['class' => 'btn btn-success']
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'=>null
        ]);
    }
}
