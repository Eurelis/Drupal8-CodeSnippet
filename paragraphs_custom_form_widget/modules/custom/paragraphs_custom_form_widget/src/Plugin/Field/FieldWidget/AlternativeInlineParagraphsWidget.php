<?php

namespace Drupal\paragraphs_custom_form_widget\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Plugin\Field\FieldWidget\InlineParagraphsWidget;

/**
 * Alternative to the default plugin implementation of the 'entity_reference paragraphs' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_paragraphs_alternative",
 *   label = @Translation("Paragraphs Classic Alternative"),
 *   description = @Translation("Alternative paragraphs inline form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class AlternativeInlineParagraphsWidget extends InlineParagraphsWidget {

  /**
   * Overrides InlineParagraphsWidget::settingsForm().
   *
   * Adds a new add mode: 'custom'.
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['add_mode']['#options']['custom'] = $this->t('Custom');

    return $form;
  }

  /**
   * Overrides InlineParagraphsWidget::settingsSummary().
   *
   * Adds a new add mode: 'custom'.
   *
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Title: @title', ['@title' => $this->getSetting('title')]);
    $summary[] = $this->t('Plural title: @title_plural', [
      '@title_plural' => $this->getSetting('title_plural')
    ]);

    switch ($this->getSetting('edit_mode')) {
      case 'open':
      default:
        $edit_mode = $this->t('Open');
        break;

      case 'closed':
        $edit_mode = $this->t('Closed');
        break;

      case 'preview':
        $edit_mode = $this->t('Preview');
        break;
    }

    switch ($this->getSetting('add_mode')) {
      case 'select':
      default:
        $add_mode = $this->t('Select list');
        break;

      case 'button':
        $add_mode = $this->t('Buttons');
        break;

      case 'dropdown':
        $add_mode = $this->t('Dropdown button');
        break;

      case 'custom':
        $add_mode = $this->t('Custom');
        break;
    }

    $summary[] = $this->t('Edit mode: @edit_mode', ['@edit_mode' => $edit_mode]);
    $summary[] = $this->t('Add mode: @add_mode', ['@add_mode' => $add_mode]);
    $summary[] = $this->t('Form display mode: @form_display_mode', [
      '@form_display_mode' => $this->getSetting('form_display_mode')
    ]);
    if ($this->getDefaultParagraphTypeLabelName() !== NULL) {
      $summary[] = $this->t('Default paragraph type: @default_paragraph_type', [
        '@default_paragraph_type' => $this->getDefaultParagraphTypeLabelName()
      ]);
    }

    return $summary;
  }

  /**
   * Overrides InlineParagraphsWidget::buildAddActions().
   *
   * Add 'add more' button, if not working with a programmed form.
   *
   * @return array
   *   The form element array.
   */
  protected function buildAddActions() {
    if (count($this->getAccessibleOptions()) === 0) {
      if (count($this->getAllowedTypes()) === 0) {
        $add_more_elements['info'] = [
          '#type' => 'container',
          '#markup' => $this->t('You are not allowed to add any of the @title types.', ['@title' => $this->getSetting('title')]),
          '#attributes' => ['class' => ['messages', 'messages--warning']],
        ];
      }
      else {
        $add_more_elements['info'] = [
          '#type' => 'container',
          '#markup' => $this->t('You did not add any @title types yet.', ['@title' => $this->getSetting('title')]),
          '#attributes' => ['class' => ['messages', 'messages--warning']],
        ];
      }

      return $add_more_elements;
    }

    if ($this->getSetting('add_mode') == 'button' || $this->getSetting('add_mode') == 'dropdown') {
      return $this->buildButtonsAddMode();
    }
    elseif ($this->getSetting('add_mode') == 'custom') {
      return $this->buildCustomAddMode();
    }

    return $this->buildSelectAddMode();
  }

  /**
   * Builds buttons for the 'custom' add_mode for adding new paragraph.
   *
   * @return array
   *   The form element array.
   */
  protected function buildCustomAddMode() {
    // Hide the button when translating.
    $add_more_elements_public = [
      '#type' => 'container',
      '#theme_wrappers' => ['paragraphs_dropbutton_wrapper'],
    ];
    $add_more_elements_private = [
      '#type' => 'container',
      '#theme_wrappers' => ['paragraphs_dropbutton_wrapper'],
    ];
    $add_more_elements_other = [
      '#type' => 'container',
      '#theme_wrappers' => ['paragraphs_dropbutton_wrapper'],
    ];

    $field_name = $this->fieldDefinition->getName();
    $title = $this->fieldDefinition->getLabel();

    foreach ($this->getAccessibleOptions() as $machine_name => $label) {
      $submitButton = [
        '#type' => 'submit',
        '#name' => strtr($this->fieldIdPrefix, '-', '_') . '_' . $machine_name . '_add_more',
        '#value' => $this->t('Add @type', ['@type' => $label]),
        '#attributes' => ['class' => ['field-add-more-submit']],
        '#limit_validation_errors' => [
          array_merge($this->fieldParents, [$field_name, 'add_more']),
        ],
        '#submit' => [[get_class($this), 'addMoreSubmit']],
        '#ajax' => [
          'callback' => [get_class($this), 'addMoreAjax'],
          'wrapper' => $this->fieldWrapperId,
          'effect' => 'fade',
        ],
        '#bundle_machine_name' => $machine_name,
      ];

      if (substr_count($machine_name, '_public')) {
        // Add class for theming.
        $submitButton['#attributes']['class'][] = 'public';

        $add_more_elements_public['add_more_button_' . $machine_name] = $submitButton;
      }
      elseif (substr_count($machine_name, '_private')) {
        // Add class for theming.
        $submitButton['#attributes']['class'][] = 'private';

        $add_more_elements_private['add_more_button_' . $machine_name] = $submitButton;
      }
      else {
        // Add class for theming.
        $submitButton['#attributes']['class'][] = 'other';

        $add_more_elements_other['add_more_button_' . $machine_name] = $submitButton;
      }
    }

    $add_more_elements = [
      '#type' => 'container',
      'add_more_elements_public' => $add_more_elements_public,
      'add_more_elements_private' => $add_more_elements_private,
      'add_more_elements_other' => $add_more_elements_other,
    ];

    return $add_more_elements;
  }

  /**
   * Overrides InlineParagraphsWidget::addMoreAjax().
   *
   * As AlternativeInlineParagraphsWidget adds a wrapping container,
   * we need to go one additional nesting level until the widgets container.
   *
   * {@inheritdoc}
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go two levels up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -3));

    // Add a DIV around the delta receiving the Ajax effect.
    $delta = $element['#max_delta'];
    $element[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
    $element[$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';

    return $element;
  }

  /**
   * Overrides InlineParagraphsWidget::addMoreSubmit().
   *
   * As AlternativeInlineParagraphsWidget adds a wrapping container,
   * we need to go one additional nesting level until the widgets container.
   *
   * {@inheritdoc}
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go two levels up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -3));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Increment the items count.
    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    if ($widget_state['real_item_count'] < $element['#cardinality'] || $element['#cardinality'] == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $widget_state['items_count']++;
    }

    if (isset($button['#bundle_machine_name'])) {
      $widget_state['selected_bundle'] = $button['#bundle_machine_name'];
    }
    else {
      $widget_state['selected_bundle'] = $element['add_more']['add_more_select']['#value'];
    }

    static::setWidgetState($parents, $field_name, $form_state, $widget_state);

    $form_state->setRebuild();
  }

  /**
   * Overrides InlineParagraphsWidget::paragraphsItemSubmit().
   *
   * As AlternativeInlineParagraphsWidget adds a wrapping container,
   * we need to go one additional nesting level until the widgets container.
   *
   * {@inheritdoc}
   */
  public static function paragraphsItemSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go four levels up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -5));

    $delta = array_slice($button['#array_parents'], -4, -3);
    $delta = $delta[0];

    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    $widget_state['paragraphs'][$delta]['mode'] = $button['#paragraphs_mode'];

    if (!empty($button['#paragraphs_show_warning'])) {
      $widget_state['paragraphs'][$delta]['show_warning'] = $button['#paragraphs_show_warning'];
    }

    static::setWidgetState($parents, $field_name, $form_state, $widget_state);

    $form_state->setRebuild();
  }

  /**
   * Overrides InlineParagraphsWidget::itemAjax().
   *
   * As AlternativeInlineParagraphsWidget adds a wrapping container,
   * we need to go one additional nesting level until the widgets container.
   *
   * {@inheritdoc}
   */
  public static function itemAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go four levels up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -5));

    $element['#prefix'] = '<div class="ajax-new-content">' . (isset($element['#prefix']) ? $element['#prefix'] : '');
    $element['#suffix'] = (isset($element['#suffix']) ? $element['#suffix'] : '') . '</div>';

    return $element;
  }

}
