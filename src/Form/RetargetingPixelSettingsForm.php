<?php
/**
 * @contains RetargetingPixelSettingsForm.php
 * User: goce
 * Date: 2/8/18
 * Time: 12:02 PM
 */

namespace Drupal\centro_basis\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * File: RetargetingPixelSettingsForm.php
 * Author: goce
 * Created:  2018.02.08
 *
 * Description:
 */
class RetargetingPixelSettingsForm extends FormBase {

  /** @var \Drupal\Core\Config\Config */
  protected $editableConfig;

  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->editableConfig = $configFactory->getEditable('centro_basis.settings');
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'centro_basis_rt_pixel_settings';
  }

  /**
   * Form constructor.
   *
   * @param array                                $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['is_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('The Retargeting Pixel is enabled'),
      '#default_value' => $this->editableConfig->get('rt_pixel.is_enabled'),
    ];

    $form['rt_pixel_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Retargeting Pixel URL'),
      '#description' => $this->t("This value will automatically get populated when you copy and paste the rt embed code (provided by Centro) in the field bellow. <br />This value should looks like: <strong>pixel-a.basis.net/iap/7f1874304d399254</strong>"),
      '#default_value' => $this->editableConfig->get('rt_pixel.uri'),
      '#size' => 100,
      '#attributes' => [
        'disabled' => "disabled",
      ],
    ];

    $form['rt_pixel_embed_code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('RT Pixel Embed Code'),
      '#description' => $this->t('Copy and paste the RT Pixel code provided by Centro here.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#button_type' => 'primary',
      ],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $embedCode = $form_state->getValue('rt_pixel_embed_code');

    // if the user didn't supply an embed code, then we don't need to make any changes.
    if (!empty($embedCode)) {
      $rtPixelPattern = '#\+\s*\'([\w\/\.-]+)\'\s*;\s*new\s+Image#';
      preg_match($rtPixelPattern, $embedCode, $matches);

      if (empty($matches[1])) {
        $form_state->setError($form['rt_pixel_embed_code'], "Ooops! We couldn't find the RT pixel location. See sample bellow.");
      }
      else {
        $form_state->setValue('rt_pixel_uri', $matches[1]);
      }
    }
  }

  /**
   * Form submission handler.
   *
   * @param array                                $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->editableConfig->set('rt_pixel.uri', $form_state->getValue('rt_pixel_uri', ''))
      ->set('rt_pixel.is_enabled', $form_state->getValue('is_enabled'), 0)
      ->save();

    Cache::invalidateTags(['config:rt_pixel']);
  }
}