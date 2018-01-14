<?php

namespace Drupal\gnuwhine_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\gnuwhine_ui\GnuwhineService;


class RecipeForm extends FormBase {
  protected $account;

  public function getFormId() {
    return 'recipe_form';
  }

  public function __construct(AccountInterface $account, GnuwhineService $gnuwhineService) {
    $this->account = $account;
    $this->gnuwhine = $gnuwhineService;
    $this->config = \Drupal::config('gnuwhine_ui.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('gnuwhine_ui.gnuwhine')
    );
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $recipes = $this->gnuwhine->getRecipes();
    $i = 0;

    foreach ($recipes as $name => $recipe) {
      $ingredients = [];
      foreach ($recipe['ingredients'] as $ingredient => $amount) {
        $ingredients[] = ucwords($ingredient) . ': ' . $amount;
      }

      $header = [
        'cocktail' => $this->t('Cocktail'),
        'ingredients' => $this->t('Ingredïents'),
        'price' => $this->t('€/@mlml', ['@ml' => $this->gnuwhine->glasssize]),
        'pours' => $this->t('Pours'),
      ];

      $options[$name] = [
        'cocktail' => [
          '#markup' => $this->gnuwhine->filtername($name),
        ],
        'ingredients' => [
          '#markup' => implode(', ', $ingredients),
        ],
        'price' => [
          '#markup' => '€' . $recipe['price'],
        ],
        'pours' => [
          '#markup' => $this->config->get($recipe['name']),
        ],
      ];

      $i++;
    }

    $form['table'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#multiple' => FALSE,
      '#options' => $options,
      '#empty' => $this->t('No recipes found'),
    );

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Pour glass'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $recipes = $this->gnuwhine->getRecipes();
    $selected = $form_state->getValue('table');

    drupal_set_message($this->t('Making your cocktail: @cocktail', ['@cocktail' => $this->gnuwhine->filtername($selected)]));
    drupal_set_message($this->t('Price to pay: €@price', ['@price' => $recipes[$selected]['price']]));

    $result = $this->gnuwhine->pour($recipes[$selected]);

    exec("python /usr/bin/Gnuwhine-Command/gpio.py --p1=1");
  }
}