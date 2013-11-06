<?php

/**
 * @file
 * Audio Cover Extractor
 *
 * This small script extracts the covers from audio files and saves them
 * in a seperate directory.
 */

// Define where the files are saved.
define('AUDIO_DIRECTORY', 'audio');

// That output will be print to the user.
$output = '';

// We need the getID3 library.
require_once('getid3/getid3/getid3.php');

/**
 * Advanced file_put_contents function which creates the path recursively.
 *
 * @param string $dir
 *   The complete path where $contents should be saved to.
 * @param string $contents
 *   The content of the file which should be saved.
 */
function file_force_put_contents($dir, $contents) {
  $parts = explode('/', $dir);
  $file = array_pop($parts);
  $dir = '';

  foreach ($parts as $key => $part) {
    $seperator = ($key == 0) ? '' : '/';
    if (!is_dir($dir .= $seperator . $part)) {
      mkdir($dir);
    }
  }

  file_put_contents($dir . '/' . $file, $contents);
}

/**
 * Helper function which delivers the corresponding to a mime type.
 *
 * @param string $mime_type
 *   The mime type
 *
 * @return string
 *   The extension.
 */
function audio_covers_get_extension($mime_type) {
  $extensions = array(
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
  );

  return $extensions[$mime_type];
}

$getID3 = new getID3();

$files = new RecursiveDirectoryIterator(AUDIO_DIRECTORY);
foreach (new RecursiveIteratorIterator($files) as $file) {
  // Skip all dot files.
  if (strpos($file->getFilename(), '.') !== 0) {
    // Analyze that audio file.
    $file_info = $getID3->analyze($file);

    if (isset($file_info['error'])) {
      foreach ($file_info['error'] as $error) {
        $output .= '<code>' . $file_info['filenamepath'] . '</code> - ' . $error . "\n";
      }
      continue;
    }

    // Extracting the cover information.
    $cover_info = isset($file_info['comments']['picture'][0]) ? $file_info['comments']['picture'][0] : array();
    // Continue only if cover information are available.
    if ($cover_info) {
      // Get the right extension for that cover.
      $cover_extension = audio_covers_get_extension($cover_info['image_mime']);
      $ext = '.' . $file->getExtension();
      // Remove the extension from the audio file so that we can replace it.
      $filename = str_replace($ext, '', $file);
      // Cut off the directory name which contains all the audio files.
      if (substr($file, 0, strlen(AUDIO_DIRECTORY)) == AUDIO_DIRECTORY) {
        $filename = substr($file, strlen(AUDIO_DIRECTORY) + 1);
      }

      // Save the cover in the right directory.
      if (file_force_put_contents('covers/' . $filename . '.' . $cover_extension, $cover_info['data']) === 0) {
        $output .= $filename . ' could not be saved.' . "\n";
      }
    }
  }
}

?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Audio Cover Extractor</title>
  </head>
  <body style="font-family: sans-serif;">
    <h1>Audio Cover Extractor</h1>
    <?php print $output; ?>
  </body>
</html>
