<?php

namespace BasaltInc\TwigTools;

// https://github.com/justinrainbow/json-schema
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

class DataSchema {

  /**
   * @param $data - Data to validate
   * @param $schema
   * @link https://github.com/justinrainbow/json-schema
   * @return Validator
   */
  public static function validate($data, $schema) {
    $validator = new Validator;
    $validator->validate($data, $schema, Constraint::CHECK_MODE_TYPE_CAST);
    // Can run `$validator->isValid()` for boolean
    // Can run `$validator->getErrors()` for errors
    return $validator;
  }

  /**
   * @param \Twig_Environment $env
   * @param $data - The data to validate, usually the `_context` / Twig `$context` var
   * @param {string} $schema_path - Twig Namespace path to schema file.
   * @param {string} $twig_self - `_self` in Twig templates which turns into `@namespace/file.twig`
   * @return {sring} $output - Place on the page below component, used for adding `<script>` tags that
   * @throws \Exception
   */
  public static function validateDataSchema(\Twig_Environment $env, $data, $schema_path, $twig_self) {
    $output = '';
    // Validate Data Schema requires Twig Debug turned on
    if (!$env->isDebug()) {
      return $output;
    }

    $schema = Utils::getDataViaTwig($env, $schema_path);

    $validator = self::validate($data, $schema);

    if (!$validator->isValid()) {
      $messages = [];
      $messages[] = '"' . $twig_self . '" had data validation errors against it\'s schema.';
      foreach ($validator->getErrors() as $error) {
        $messages[] = sprintf("`%s` %s", $error['property'], $error['message']);
      }
      $messages[] = ''; // just a little space
      $message_to_log = join('
', $messages);

      $to_log = [
        'message' => $message_to_log,
        'details' => [
          'template_path' => Utils::resolveTwigPath($env, $twig_self),
          'data_passed_to_template' => $data,
          'schema' => $schema,
          'errors' => $validator->getErrors(),
        ],
      ];

      // @todo Consider if/how best to have Pattern Lab compile fail if a validation error is found; which is done with:
      // \PatternLab\Console::writeError($message_to_log);

      $output = Utils::consoleLog($to_log, 'error', ['message', 'details'], true);
    }

    return $output;
  }
}
