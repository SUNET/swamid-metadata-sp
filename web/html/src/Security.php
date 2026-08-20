<?php
namespace metadata;

/**
 * Context-specific security helpers for values originating in metadata.
 */
final class Security {
  /**
   * Encode a value for an HTML text or quoted-attribute context.
   *
   * @param mixed $value Value to encode
   *
   * @return string
   */
  public static function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }

  /**
   * Check whether a language is enabled for the federation.
   *
   * @param string $language Language code supplied by an editor
   * @param array $allowedLanguages Configured language codes
   *
   * @return bool
   */
  public static function isAllowedLanguage($language, $allowedLanguages) {
    $language = strtolower(trim($language));
    $allowedLanguages = array_map(
      static function ($allowedLanguage) {
        return strtolower(trim($allowedLanguage));
      },
      $allowedLanguages
    );

    return $language !== '' && in_array($language, $allowedLanguages, true);
  }

  /**
   * Render an HTTP(S) URL as a link, or inert text for every other scheme.
   *
   * @param string $url URL to render
   * @param string|null $text Link text; defaults to the URL
   * @param string $class Optional CSS class assembled by application code
   *
   * @return string
   */
  public static function httpLink($url, $text = null, $class = '') {
    $text = $text === null ? $url : $text;
    $escapedText = self::escape($text);
    if (! self::isHttpUrl($url)) {
      return $escapedText;
    }

    $classAttribute = preg_match('/\A[A-Za-z0-9_-]+\z/', $class)
      ? sprintf(' class="%s"', self::escape($class))
      : '';
    return sprintf(
      '<a href="%s"%s target="_blank" rel="noopener noreferrer">%s</a>',
      self::escape($url),
      $classAttribute,
      $escapedText
    );
  }

  /**
   * Return response headers for untrusted raw metadata.
   *
   * Inline views are plain text. Downloads retain their XML media type but are
   * forced to an attachment so active foreign XML cannot execute in origin.
   *
   * @param bool $download Whether the response is a download
   *
   * @return array
   */
  public static function rawXmlHeaders($download) {
    $headers = array('X-Content-Type-Options: nosniff');
    if ($download) {
      $headers[] = 'Content-Type: application/xml; charset=utf-8';
      $headers[] = 'Content-Disposition: attachment; filename=metadata.xml';
    } else {
      $headers[] = 'Content-Type: text/plain; charset=utf-8';
    }

    return $headers;
  }

  /**
   * Check that a URL has an explicit HTTP(S) scheme and host.
   *
   * @param string $url URL to check
   *
   * @return bool
   */
  private static function isHttpUrl($url) {
    if ($url === '' || trim($url) !== $url || preg_match('/[\x00-\x1F\x7F]/', $url)) {
      return false;
    }

    $parts = parse_url($url);
    return $parts !== false
      && isset($parts['scheme'], $parts['host'])
      && in_array(strtolower($parts['scheme']), array('http', 'https'), true)
      && $parts['host'] !== '';
  }
}
