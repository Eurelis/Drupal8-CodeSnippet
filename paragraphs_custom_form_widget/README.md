# paragraphs_custom_form_widget
## Purpose

This repository serves as an example on how we can customize the paragraphs' "add more" form widget from *paragraphs* module.

![Add More customized](https://github.com/Eurelis/Eurelis-Drupal8-CodeSnippet/blob/master/paragraphs_custom_widget_form/readme/capture.png)

## Target

Tested on Drupal 8.3.1.

## Folder structure / important files

* *modules/custom/paragraphs_custom_form_widget*:
  > It contains the module which adds the custom Paragraphs form widget.
* *themes/custom/mytheme*:
  > A theme customization sample. It extends from the *Seven* theme.

## How to use it

1. Install a Drupal site.
2. Install the *Paragraphs Custom Form Widget* (`paragraphs_custom_widget_form`) module.
3. Install the *mytheme* theme and use it by default.
2. Create some paragraph types:
   * make some paragraph types which contains `_private` in the machine name,
   * make some paragraph types which contains `_public` in the machine name,
   * make some paragraph types which do not contains `_public` nor `_private`,
3. On a content type like the basic Article, add a paragraphs field.
4. On this field, select the *Paragraphs Classic Alternative* widget.
5. In the field options (the gear button), select *Custom*.
6. Go to the Article edit page:
   > => You will see buttons separated by their names (private, public and others).

## Additionnal Notes & Caveats

* The admin theme is not used by the *Paragraphs* module when using the *Quick Edit* feature; it uses the frontoffice theme. The looks will not be consistent between frontoffice and backoffice. A possible and dirty workaround is to include the styles directly into the module (style attribute on the render array).
* The Paragraphs module does not make a good use of Twig templates. The authors may or may not refactor the templating system to enable theme customization of the backoffice.
