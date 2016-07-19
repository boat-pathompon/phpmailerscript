<?php
  require("PHPMailer/PHPMailerAutoload.php");

  // DATE CONFIGs.
  date_default_timezone_set('Etc/UTC');

  // VALIDATION CONFIGs.
  define('ALLOWED_EXTENSIONS', array('jpg', 'png', 'gif')); // Leave blank array for allowed all.
  define('FILE_SIZE_LIMIT',    10 * 1024); // 10 MB

  // EMAIL CONFIGs.
  define('USERNAME'     , '');
  define('PASSWORD'     , '');
  define('FROM_ADDRESS' , '');
  define('FROM_NAME'    , '');
  define('TO_ADDRESS'   , '');
  define('REPLY_ADDRESS', '');
  define('REPLY_NAME'   , '');

  // Get uploded file informations.
  //   Return: Object with fileName, tmpName, extension, filesizeKB
  function getFileInfo($inputName) {
    $fileName   = basename($_FILES[$inputName]['name']);
    $tmpName    = $_FILES[$inputName]['tmp_name'];
    $extension  = substr($fileName, strrpos($fileName, '.') + 1);
    $fileSizeKB = $_FILES[$inputName]["size"] / 1024;

    return (object) array(
      'fileName'   => $fileName,
      'tmpName'    => $tmpName,
      'extension'  => $extension,
      'fileSizeKB' => $fileSizeKB
    );
  }

  // Validate file.
  //   Return: True when file was invalid.
  function validateFile($fileInfo) {
    $errors = array();

    if (!_extensionAllowed($fileInfo->extension))
      array_push($errors, 'file_extension_not_allowed');

    if ($fileInfo->fileSizeKB > FILE_SIZE_LIMIT)
      array_push($errors, 'file_size_exceed_maximum');

    return (object) array(
      'valid'  => (sizeof($errors) == 0),
      'errors' => $errors
    );
  }

  // Validate extension.
  function _extensionAllowed($extension) {
    if (sizeof(ALLOWED_EXTENSIONS) == 0)
      return true;

    for ($i = 0 ; $i < sizeof(ALLOWED_EXTENSIONS) ; $i++)
      if (strcasecmp(ALLOWED_EXTENSIONS[$i], $extension) == 0)
        return true;

    return false;
  }

  // Convert params from form inputs to email pbody.
  function emailBody() {
    $keys = array_keys($_POST);
    $text = '';
    $html = '';

    for ($i = 0 ; $i < sizeof($keys) - 1 ; $i++) {
      $name = $keys[$i];
      $value = empty($_POST[$name]) ? '-' : $_POST[$name];
      $html .= '<b>' . $name . ' :</b><br />' . $value . '<br/>';
      $text .= $name . ':' . $value . '\n';
    }

    return (object) array(
      'html'  => $html,
      'text'  => $text
    );
  }

  // Convert uploaded files to params for email.
  // Return:
  // {
  //    errors: [
  //      [fileName, errorsArray]
  //    ],
  //    files: [
  //      [validFileTmpName, validFileName]
  //    ]
  // }
  function emailFiles() {
    $keys = array_keys($_FILES);
    $files = array();
    $errors = array();

    for ($i = 0 ; $i < sizeof($keys) ; $i++) {
      $fileInfo      = getFileInfo($keys[$i]);
      $validatedFile = validateFile($fileInfo);

      if ($validatedFile->valid) {
        array_push(
          $files,
          array(
            $fileInfo->tmpName,
            $fileInfo->fileName
          )
        );
      }
      else {
        array_push(
          $errors,
          array(
            $keys[$i],
          $validatedFile->errors
          )
        );
      }
    }

    return (object) array(
      'errors' => $errors,
      'files' => $files,
    );
  }

  function initMail($emailBody, $emailFiles) {
    $mail = new PHPMailer;
    $mail->IsSMTP();
    // $mail->Debugoutput = 'html';
    // $mail->SMTPDebug   = 2;
    $mail->SMTPAuth    = true;
    $mail->Host        = 'smtp.gmail.com';
    $mail->SMTPSecure  = 'tls';
    $mail->Port        = 587;
    $mail->Username    = USERNAME;
    $mail->Password    = PASSWORD;

    $mail->isHTML(true);
    $mail->setFrom(FROM_ADDRESS, FROM_NAME);
    $mail->addAddress(TO_ADDRESS);
    $mail->addReplyTo(REPLY_ADDRESS, REPLY_NAME);

    $mail->Subject = 'Test send mail from PHPMailer';
    $mail->Body    = $emailBody->html;
    $mail->AltBody = $emailBody->text;

    for ($i = 0 ; $i < sizeof($emailFiles) ; $i++)
      $mail->addAttachment($emailFiles[$i][0], $emailFiles[$i][1]);

    return $mail;
  }
?>

<?php
  $emailFiles = emailFiles();

  if (sizeof($emailFiles->errors) > 0) {
    echo 'errors';
  }
  else {
    $emailBody = emailBody();
    $mail = initMail($emailBody, $emailFiles->files);

    if ($mail->send())
      echo 'Mail was sent.';
    else
      echo 'Mailer Error: ' . $mail->ErrorInfo;
  }
?>
