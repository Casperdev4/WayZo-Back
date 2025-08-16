<?php

namespace App\Form;

use App\Entity\Chauffeur;
use App\Entity\Course;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourseTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomClient', TextType::class)
            ->add('clientContact', TextType::class) // à ajouter dans ton entité si pas encore présent
            ->add('depart', TextType::class)
            ->add('arrivee', TextType::class)
            ->add('date', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('heure', TimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('passagers', ChoiceType::class, [
                'choices' => array_combine(range(1, 12), range(1, 12)),
            ])
            ->add('luggage', ChoiceType::class, [
                'choices' => array_combine(range(0, 8), range(0, 8)),
            ])
            ->add('vehicle', ChoiceType::class, [
                'choices' => [
                    'Classe E' => 'Classe E',
                    'Classe S' => 'Classe S',
                    'Classe V' => 'Classe V',
                    'Autre' => 'Autre',
                ],
            ])
            ->add('boosterSeat', ChoiceType::class, [
                'choices' => array_combine(range(0, 3), range(0, 3)),
            ])
            ->add('babySeat', ChoiceType::class, [
                'choices' => array_combine(range(0, 3), range(0, 3)),
            ])
            ->add('prix')
            ->add('comment', TextareaType::class, [
                'required' => false,
            ])
            ->add('statut', TextType::class)
            ->add('statutExecution', TextType::class)
            ->add('departVersClient', TextType::class)
            ->add('ClientPrisEnCharge', TextType::class)
            ->add('ArriveeDestination', TextType::class)
            ->add('chauffeurVendeur', EntityType::class, [
                'class' => Chauffeur::class,
                'choice_label' => 'id',
            ])
            ->add('chauffeurAccepteur', EntityType::class, [
                'class' => Chauffeur::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}

