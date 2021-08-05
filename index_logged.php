<?php
// We need to use sessions, so you should always start sessions using the below code.
session_start();
// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['loggedin'])) {
  header('Location: index.html');
  exit;
}
$conn = mysqli_connect('***', '***', '***', '***');
if (!$conn) {
  echo 'Connection error: ' . mysqli_connect_error();
}
$date_arr = mysqli_query($conn, "SELECT * FROM last_update_time;");
if ($date_arr->num_rows > 0) {
  while($d = mysqli_fetch_array($date_arr)){
    $datetime = $d['datetime'];
  };
};
?>
<!DOCTYPE html>
<html lang="pl-PL">

<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" type="text/css" href="normalize.css" />
  <script data-require="jquery@2.2.4" data-semver="2.2.4" src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
  <link data-require="bootstrap@3.3.7" data-semver="3.3.7" rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
  <script data-require="bootstrap@3.3.7" data-semver="3.3.7" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.1/js/bootstrap-select.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.1/css/bootstrap-select.min.css" />
  <link rel="stylesheet" type="text/css" href="style.css" />
  <link rel="icon" href="icon.png" />
  <title>
    Desktop Screenshots
  </title>
</head>

<body>
  <nav>
    <div>
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFFFFF">
        <path d="M0 0h24v24H0V0z" fill="none" />
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM7.07 18.28c.43-.9 3.05-1.78 4.93-1.78s4.51.88 4.93 1.78C15.57 19.36 13.86 20 12 20s-3.57-.64-4.93-1.72zm11.29-1.45c-1.43-1.74-4.9-2.33-6.36-2.33s-4.93.59-6.36 2.33C4.62 15.49 4 13.82 4 12c0-4.41 3.59-8 8-8s8 3.59 8 8c0 1.82-.62 3.49-1.64 4.83zM12 6c-1.94 0-3.5 1.56-3.5 3.5S10.06 13 12 13s3.5-1.56 3.5-3.5S13.94 6 12 6zm0 5c-.83 0-1.5-.67-1.5-1.5S11.17 8 12 8s1.5.67 1.5 1.5S12.83 11 12 11z" />
      </svg>
      <p class="nav-text"><?= $_SESSION['name'] ?></p>
    </div>
    <div>
      <img id="sn_logo" src="./sn_logo.png" alt="sn_logo">
    </div>
    <div class="update-time">
      <p>Aktualizacja: <?= $datetime ?></p>
    </div>
    <div>
      <a href="./logout.php">
        <p class="nav-text">Wyloguj</p>
        <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" viewBox="0 0 24 24" fill="#FFFFFF">
          <g>
            <path d="M0,0h24v24H0V0z" fill="none" />
          </g>
          <g>
            <path d="M17,8l-1.41,1.41L17.17,11H9v2h8.17l-1.58,1.58L17,16l4-4L17,8z M5,5h7V3H5C3.9,3,3,3.9,3,5v14c0,1.1,0.9,2,2,2h7v-2H5V5z" />
          </g>
        </svg>
      </a>
    </div>
  </nav>
  <form method="get" action="">
    <div class="search-box">
      <div class="select">
        <select class="select-text" required name='siec'>
          <option selected disabled></option>
          <option value="%">Wszystkie sieci</option>
          <?php
          $records = mysqli_query($conn, "SELECT siec From desktop_ss GROUP BY siec;");
          while ($data = mysqli_fetch_array($records)) {
            echo "<option value='" . $data['siec'] . "'>" . $data['siec'] . "</option>";
          };
          ?>
        </select>
        <span class="select-highlight"></span>
        <span class="select-bar"></span>
        <label class="select-label">Wybierz sieć</label>
      </div>
      <div class="select">
        <select class="select-text" required name='status'>
          <option selected disabled></option>
          <option value="%">Wszystkie statusy</option>
          <option value="1">Połączony</option>
          <option value="0">Niepołączony</option>
        </select>
        <span class="select-highlight"></span>
        <span class="select-bar"></span>
        <label class="select-label">Wybierz status</label>
      </div>
      <div">
        <button type="submit" class="btn btn-primary mb-2">Wyszukaj</button>
    </div>
    </div>
  </form>
  <?php
  echo '<div id="result" class="main-box">';
  if (isset($_GET['siec']) || isset($_GET['status'])) {
    $net = $_GET['siec'];
    $state = $_GET['status'];
    $records = mysqli_query($conn, "SELECT * FROM desktop_ss WHERE siec LIKE '$net' AND connection LIKE '$state' ORDER BY sn;");
  };
  if ($records->num_rows < 1) {
    echo '  <div class="empty-array">';
    echo '    <p>Brak komputerów dla wybranych parametrów</p>';
    echo '  </div>';
  } else {
    echo '  <div class="grid-container">';
    while ($r = mysqli_fetch_array($records)) {
      echo '    <div class="grid-container-cell">';
      echo '      <img src="./ss/' . $r['sn'] . '/screenshot.jpg" alt="' . $r['sn'] . '" onclick="window.open(this.src,' . "'_blank'" . ');">';
      echo '      <table>';
      echo '        <tr>';
      echo '          <th class="grid-item-text">' . $r['sn'] . '</th>';
      echo '          <td class="grid-item-text">' . $r['placowka'] . '</th>';
      echo '        </tr>';
      echo '      </table>';
      echo '      <div class="btn-container">';
      if ($r['ip'] != 'NULL'){
        echo '        <button type="button" class="btn btn-sm" onclick="location.href=' . "'" . 'snftp://' . $r['ip'] . "'" . '">FTP</button>';
        echo '        <button type="button" class="btn btn-sm" onclick="location.href=' . "'" . 'snvnc://' . $r['ip'] . "'" . '">VNC</button>';
      } else {
        echo '        <button type="button" class="btn btn-sm" disabled>FTP</button>';
        echo '        <button type="button" class="btn btn-sm" disabled>VNC</button>';
      }
      echo '      </div>';
      echo '    </div>';
    }
    echo '  </div>';
    echo '  <div class="footer">';
    echo '    <p>Copyright &copy; 2021 ***</p>';
    echo '    <p>Made by <a href="http://exiges.pl">Exiges</a></p>';
    echo '  </div>';
  }
  echo '</div>';
  mysqli_close($conn);
  ?>
</body>

</html>
