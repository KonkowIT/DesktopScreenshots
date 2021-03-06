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
    while ($d = mysqli_fetch_array($date_arr)) {
        $datetime = $d['datetime'];
    };
};
?>
<!DOCTYPE html>
<html lang="pl-PL">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="cache-control" content="no-cache" />
    <meta http-equiv="expires" content="0" />
    <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
    <meta http-equiv="pragma" content="no-cache" />
    <link rel="stylesheet" type="text/css" href="normalize.css" />
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link data-require="bootstrap@3.3.7" data-semver="3.3.7" rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
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
            <a href="http://***DesktopScreenshots/index_logged.php">
                <img id="sn_logo" src="./sn_logo.png" alt="sn_logo">
            </a>
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
                    <option style="display: none;" selected disabled></option>
                    <?php
                    echo '<option value="%" ' . ($_GET['siec'] == '%' ? 'selected' : '') . '>Wszystkie sieci</option>';
                    $records = mysqli_query($conn, "SELECT siec From desktop_ss GROUP BY siec;");
                    while ($data = mysqli_fetch_array($records)) {
                        echo "<option value='" . $data['siec'] . "' " . ($_GET['siec'] == $data['siec'] ? 'selected' : '') . ">" . $data['siec'] . "</option>";
                    };
                    ?>
                </select>
                <span class="select-highlight"></span>
                <span class="select-bar"></span>
                <label class="select-label">Wybierz sie??</label>
            </div>
            <div class="select">
                <select class="select-text" required name='status'>
                    <option style="display: none;" selected disabled></option>
                    <?php
                    echo "<option value='%' " . ($_GET['status'] == '%' ? 'selected' : '') . ">Wszystkie statusy</option>
                    <option value='1' " . ($_GET['status'] == '1' ? 'selected' : '') . ">Po????czony</option>
                    <option value='0' " . ($_GET['status'] == '0' ? 'selected' : '') . ">Niepo????czony</option>";
                    ?>
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
    } else {
        $table = mysqli_query($conn, "SELECT siec, count(siec) AS siec_cntr, COUNT(IF (`connection` = 1, 1, NULL)) AS conn_cntr, COUNT(IF (download_error = 1, 1, NULL)) AS download_cntr from desktop_ss group by siec;");
        if ($table->num_rows > 0) {
            echo '  <table class="table table-hover table-dark summary">
    <thead class="table-dark">
      <tr>
        <th>Sie??</th>
        <th>Wszystkie</th>
        <th>Po????czone</th>
        <th>Niepo????czone</th>
        <th>B??ad pobierania</th>
      </tr>
    </thead>
    <tbody>';
            while ($t = mysqli_fetch_array($table)) {
                echo '      <tr>
          <td class="summary-net">' . $t['siec'] . '</td>
          <td>' . $t['siec_cntr'] . '</td>
          <td>' . $t['conn_cntr'] . '</td>
          <td>' . ($t['siec_cntr'] - $t['conn_cntr']) . '</td>
          <td>' . $t['download_cntr'] . '</td>
        </tr>';
            };
            echo '    </tbody>
  </table>';
        };
    };

    if ($records->num_rows < 1) {
        echo '  <div class="empty-array">
    <p>Brak komputer??w dla wybranych parametr??w</p>
  </div>';
    } else {
        echo '  <div class="grid-container">';
        while ($r = mysqli_fetch_array($records)) {
            if ($r['connection'] == 1) {
                echo '    <div class="grid-container-cell">';
            } else {
                echo '    <div class="grid-container-cell disconnected">';
            }
            echo '      <img src="./ss/' . $r['sn'] . '/screenshot.jpg" alt="' . $r['sn'] . '" onclick="window.open(this.src,' . "'_blank'" . ');">
      <table>
        <tr>';
            if ($r['connection'] == 0) {
                echo '          <td  colspan="5" class="grid-item-text disc-time">Ostatni zrzut: ' . substr_replace(substr_replace(date("dmY H:i", filemtime('./ss/' . $r['sn'] . '/screenshot.jpg')), "/", 2, 0), "/", 5, 0)  . '</td>
        </tr>
        <tr>';
            }
            echo '          <th colspan="1" class="grid-item-text">' . $r['sn'] . '</th>
          <td colspan="4" class="grid-item-text">' . $r['placowka'] . '</th>
        </tr>
      </table>
      <div class="btn-container">';
      if ($r['ip'] != 'NULL') {
          echo '        <button type="button" class="btn btn-sm" onclick="location.href=' . "'" . 'snftp://' . $r['ip'] . "'" . '">FTP</button>
        <button type="button" class="btn btn-sm" onclick="location.href=' . "'" . 'snvnc://' . $r['ip'] . "'" . '">VNC</button>
        <button type="button" class="btn btn-sm" onclick="location.href=' . "'" . 'snssh://' . $r['ip'] . "'" . '">SSH</button>
        <input class="reload" type="button"  id="reload_' . $r['sn'] . '" onclick="reload(' . "'" . $r['ip'] . "', '" . $r['sn'] . "'" . ')" value="">';
      } else {
          echo '        <button type="button" class="btn btn-sm" disabled>FTP</button>
        <button type="button" class="btn btn-sm" disabled>VNC</button>
        <button type="button" class="btn btn-sm" disabled>SSH</button>';
      }
      echo '      </div>
    </div>';
        }
        echo '  </div>
  <div class="footer">
    <p>Copyright &copy; 2021 ***</p>
    <p>Made by <a href="http://exiges.pl">Exiges</a></p>
  </div>';
    }
    echo '</div>';
    mysqli_close($conn);
    ?>
    <script type="text/javascript">
    function sleep(time) {
      return new Promise((resolve) => setTimeout(resolve, time));
    }

    function reload(ipVal, snVal) {
      console.log("Downloading new screenshot from computer: " + snVal + ", " + ipVal);
      $.ajax({
        url: 'reload.php',
        type: 'POST',
        data: {
          ip: ipVal,
          sn: snVal
        },
        success: function(data) {
          if (data != "") {
            console.log(data);
          }
        }
      });

      sleep(1000);
      var url = $('#'+snVal).attr("src");
      $('#'+snVal).attr("src", url + `?v=${Math.random()}`);
    }
  </script>
</body>

</html>
