$host.UI.RawUI.WindowTitle = "DesktopScreenshots"

$homeDir = "C:\SN_Scripts\DesktopScreenshots"
$updt = @()
$dontCheck = @()
$apiData = @()
$groups = @(
  "Network_1",
  "Network_2",
  "Network_3"
)

# API
$requestURL = '***'
$requestHeaders = @{'sntoken' = '***'; 'Content-Type' = 'application/json' }

class FileObject {
  [string]$File
  [string]$DestinationPath
}

function GetComputersFromAPI {
  [CmdletBinding()]
  param(
    [parameter(ValueFromPipeline)]
    [ValidateNotNull()]
    [String]$networkName,
    [Array]$dontCheck
  )
    
  # Body
  $requestBody = @"
{

"network": ["$($networkName)"]

}
"@

  # Request
  try {
    $request = Invoke-WebRequest -Uri $requestURL -Method POST -Body $requestBody -Headers $requestHeaders -ea Stop
  }
  catch [exception] {
    $Error[0]
    Exit 1
  }

  # Creating PS array of sn
  if ($request.StatusCode -eq 200) {
    $requestContent = $request.content | ConvertFrom-Json
  }
  else {
    Write-host ( -join ("Received bad StatusCode for request: ", $request.StatusCode, " - ", $request.StatusDescription)) -ForegroundColor Red
    Exit 1
  }

  $snList = @()
  $requestContent | ForEach-Object {
    if ((!($dontCheck -match $_.name)) -and ($_.lok -ne "LOK0014")) {
      $hash = [ordered]@{
        sn       = $_.name;
        ip       = $_.ip;
        lok_id   = $_.lok
        placowka = $_.lok_name.toString();
        siec     = $networkName
      }

      $snList = [array]$snList + (New-Object psobject -Property $hash)
    }
  }

  return $snList
}

function sql {
  param (
    $request,
    [switch] $select,
    [switch] $ins_upd
  )

  $server = "***"
  $port = 1234
  $user = "****"
  $database = "***"
  $pswd = "***"

  [void][System.Reflection.Assembly]::LoadFrom("C:\Program Files (x86)\MySQL\MySQL Connector Net 8.0.26\Assemblies\v4.5.2\MySql.Data.dll")

  try {
    $connection = New-Object MySql.Data.MySqlClient.MySqlConnection
    $command = New-Object MySql.Data.MySqlClient.MySqlCommand
    $connection.ConnectionString = “server=$server;user id=$user;password=$pswd;database=$database;port=$port;pooling=false;connectionTimeout=120;”
    $connection.Open()
    $command.Connection = $connection
    foreach ($r in $request) {
      if (($null -ne $r) -or ($r -ne "")){
        $command.CommandText = "$r"
        if ($select) {
          $rdr = $command.ExecuteReader()
          $tbl = New-Object Data.DataTable
          $tbl.Load($rdr)
          $rdr.Close()
        }
        elseif ($ins_upd) {
          $command.ExecuteNonQuery() | out-null
        }
      }
    }

    $connection.Close()
  }
  catch {
    Write-host "Error while sending request to database: $($_.exception.message)" -ForegroundColor Red
    $tbl =  0
  }

  return $tbl
}

function Start-SleepTimer($seconds) {
  $doneDT = (Get-Date).AddSeconds($seconds)
  while ($doneDT -gt (Get-Date)) {
    $secondsLeft = $doneDT.Subtract((Get-Date)).TotalSeconds
    $percent = ($seconds - $secondsLeft) / $seconds * 100
    Write-Progress -activity "Desktop screenshots" -Status "Ponowne uruchomienie skryptu za" -SecondsRemaining $secondsLeft -PercentComplete $percent
    [System.Threading.Thread]::Sleep(500)
  } 
  Write-Progress -activity "Desktop screenshots" -Status "Ponowne uruchomienie skryptu za" -SecondsRemaining 0 -Completed
}

if (!(Test-Path "C:\Program Files (x86)\WinSCP\WinSCPnet.dll")) {
  "`n"
  Write-Host "Brak zainstalowanego programu WinSCP! Zainstaluj, a nastepnie ponow uruchomienie skryptu" -ForegroundColor red -BackgroundColor Black
  "`n"
  Start-Sleep -s 3

  $Browser = new-object -com internetexplorer.application
  $Browser.navigate2("https://winscp.net/eng/downloads.php#additional")
  $Browser.visible = $true
  break
}

while (!(Test-Connection -ComputerName "10.0.0.1" -Count 3 -Quiet)) {
  Write-Host "VPN not connected!" -ForegroundColor Red
  Start-Sleep -Seconds 30
}

Get-Content "C:\Metadane_do_skryptow\sn_disabled_DesktopScreenshots.txt" -ErrorAction SilentlyContinue | ForEach-Object { 
  if ($dontCheck -notcontains $_) {
    $dontCheck = [array]$dontCheck + $_
  }
}

Foreach ($g in $groups) {
  [array]$apiData += (GetComputersFromAPI -networkName "$g" -dontCheck $dontCheck)
}

$db = sql -sel -request "SELECT * FROM desktop_ss;"

# Creating / Updating records in database
Foreach ($d in $apiData) {
  $db_chk = $db | ? { $_.sn -eq $d.sn }
  if ($null -ne $db_chk.sn) {
    if (($db_chk.placowka -ne $d.placowka) -or ($db_chk.lok_id -ne $d.lok_id) -or ($db_chk.ip -ne $d.ip) -or ($db_chk.siec -ne $d.siec)){
      "Updating record '$($d.sn)'"
      [array]$updt += "UPDATE desktop_ss SET placowka = '$($d.placowka)', lok_id = '$($d.lok_id)', ip = '$($d.ip)', siec = '$($d.siec)' WHERE sn = '$($d.sn)';"  
    }
  }
  else {
    "Inserting record '$($d.sn)'"
    [array]$updt += "INSERT INTO desktop_ss (sn, placowka, lok_id, ip, siec) VALUES ('$($d.sn)', '$($d.placowka)', '$($d.lok_id)', '$($d.ip)', '$($d.siec)');"
  }
}

# Deleting records in database
foreach ($b in $db) {
  if (!($apiData.sn -contains $b.sn)) {
    "Deleting record '$($b.sn)'"
    [array]$updt += "DELETE FROM desktop_ss WHERE sn='$($b.sn)';"
  }
}

if ($null -ne $updt) {
  sql -ins_upd -request $updt
}

$dbActual = sql -sel -request "SELECT * FROM desktop_ss;"

# Jobs
Foreach ($comp in $dbActual) {
  $allJobs = @(Get-Job | ? { $_.State -eq 'Running' })
  if ($allJobs.Count -ge 25) {
    $allJobs | Wait-Job -Any | Out-Null
  }

  $scriptBlock = {
    $sn = $args[0].sn
    $ip = $args[0].ip
    $lok = $args[0].placowka
    $homeDir = $args[1]

    $downloadDir = "$homeDir\ss\$sn"

    function sql {
      param (
        $request,
        [switch] $select,
        [switch] $ins_upd
      )
    
      $server = "***"
      $port = 1234
      $user = "***"
      $database = "***"
      $pswd = "***"
    
      [void][System.Reflection.Assembly]::LoadFrom("C:\Program Files (x86)\MySQL\MySQL Connector Net 8.0.26\Assemblies\v4.5.2\MySql.Data.dll")
    
      try {
        $connection = New-Object MySql.Data.MySqlClient.MySqlConnection
        $command = New-Object MySql.Data.MySqlClient.MySqlCommand
        $connection.ConnectionString = “server=$server;user id=$user;password=$pswd;database=$database;port=$port;pooling=false;connectionTimeout=120;”
        $connection.Open()
        $command.Connection = $connection
        foreach ($r in $request) {
          $command.CommandText = "$r"
          if ($select) {
            $rdr = $command.ExecuteReader()
            $tbl = New-Object Data.DataTable
            $tbl.Load($rdr)
            $rdr.Close()
          }
          elseif ($ins_upd) {
            $command.ExecuteNonQuery() | out-null
          }
        }
    
        $connection.Close()
      }
      catch {
        Write-host "Error while sending request to database: $($_.exception.message)" -ForegroundColor Red
        $tbl =  0
      }
    
      return $tbl
    }

    if (!(Test-path $downloadDir)) {
      New-item $downloadDir -ItemType Directory -Force | out-null
    }

    if ($ip -ne "null") {
      try { 
        Add-Type -Path "$(${env:ProgramFiles(x86)})\WinSCP\WinSCPnet.dll" -ErrorAction Stop

        $sessionOptions = New-Object WinSCP.SessionOptions -Property @{
          Protocol   = [WinSCP.Protocol]::ftp
          HostName   = $ip
          PortNumber = 1
          UserName   = "***"
          Password   = "***"
        }

        $session = New-Object WinSCP.Session

        try {
          $session.Open($sessionOptions)
          $transferOptions = New-Object WinSCP.TransferOptions
          $transferOptions.TransferMode = [WinSCP.TransferMode]::Binary
          $transferOptions.OverwriteMode.Overwrite
        
          $fileList = ($session.ListDirectory("/screennetwork/player")).Files
          $fileList | Where-Object { $_.Name -eq "screenshot.jpg" } | ForEach-Object {
            if (Test-Path "$downloadDir\screenshot.jpg") {
              Remove-Item "$downloadDir\screenshot.jpg" -Force
            }
            
            if ($_.LastWriteTime -gt ((get-date).AddMinutes(-30))) {
              Write-host "`nDownloading screenshot from:"$sn" - "$lok
              $result = $session.GetFiles("/screennetwork/player/screenshot.jpg", "$downloadDir\screenshot.jpg", $False, $transferOptions)
              Write-host "Download complete: $($result.IsSuccess)"
              sql -ins_upd -request "UPDATE desktop_ss SET connection = true, download_error = false WHERE sn = '$sn';"
            }
            else {
              Write-host "`nBrak nowego zrzutu ekranu: $sn - $lok" -ForegroundColor Red
              copy-item "$homeDir\errorNew.jpg" -Destination "$downloadDir\screenshot.jpg" -Force 
              sql -ins_upd -request  "UPDATE desktop_ss SET connection = true, download_error = true WHERE sn = '$sn';"
            }
          }        
        }
        catch {
          $eMsg = ( -join ("`n", $_.Exception.Message, "`n`nLine ", $error[0].InvocationInfo.ScriptLineNumber, " : " + ($error[0].InvocationInfo.Line | Out-String).Trim() ))
          Write-Host "Blad pobierania zrzutu ekranu" -ForegroundColor Red
          Write-Host $eMsg
          copy-item "$homeDir\errorDownload.jpg" -Destination "$downloadDir\screenshot.jpg" -Force 
          sql -ins_upd -request  "UPDATE desktop_ss SET connection = true, download_error = true WHERE sn = '$sn';"
        }
        finally {
          $session.Dispose()
        }
      }
      catch {
        Write-host "`nKomputer jest niepolaczony: $sn - $lok"  -ForegroundColor Red
        sql -ins_upd -request  "UPDATE desktop_ss SET connection = false, download_error = false WHERE sn = '$sn';"
      }
    }
    else {
      Write-host "`nKomputer jest niepolaczony: $sn - $lok"  -ForegroundColor Red
      sql -ins_upd -request  "UPDATE desktop_ss SET connection = false, download_error = false WHERE sn = '$sn';"
    }
  }

  Start-Job -ScriptBlock $scriptBlock -Name $comp.sn -ArgumentList $comp, $homeDir
}

Wait-Job * | Out-Null

foreach ($job in Get-Job) {
  $result = Receive-Job $job
  Write-Host $result
}

Remove-Job -State Complete

Add-Type -Path "$(${env:ProgramFiles(x86)})\WinSCP\WinSCPnet.dll" -ErrorAction Stop
$lcRemotePath = "/www/public/DesktopScreenshots"

$sessionOptions = New-Object WinSCP.SessionOptions -Property @{
  Protocol   = [WinSCP.Protocol]::ftp
  HostName   = "***"
  PortNumber = 1
  UserName   = "***"
  Password   = "***"
}

$session = New-Object WinSCP.Session
$session.Open($sessionOptions)
$transferOptions = New-Object WinSCP.TransferOptions
$transferOptions.TransferMode = [WinSCP.TransferMode]::Binary
$transferOptions.OverwriteMode.Overwrite
$transferOptions.FilePermissions = New-Object WinSCP.FilePermissions
$transferOptions.FilePermissions.Octal = "777"

$files = ( @(
  Get-ChildItem "$homeDir\ss" -File -Recurse | ForEach-Object { 
    $dir = Split-Path $_.Directory -Leaf
    [FileObject]@{ File = "$($_.Directory)\screenshot.jpg"; DestinationPath = "$lcRemotePath/ss/$dir/" } 
  }
))

$fileList = ($session.ListDirectory("$lcRemotePath/ss")).Files
""
Foreach ($f in $files) {
  $par = Split-Path -leaf (Split-Path -Parent $f.file)
  if ((!($fileList.Name -contains $par)) -and $f.DestinationPath -ne "$lcRemotePath/") {
      Write-host "Creating directory for computer $par on server"
      $session.CreateDirectory("$lcRemotePath/ss/$par")
  }

  $r = $session.PutFiles($f.File, $f.DestinationPath, $False, $transferOptions)
  if ($r.IsSuccess -eq $true) {
      Write-host "Uploading '$($f.File)' to server completed successfully"
  }
  else {
      Write-host "Uploading '$($f.File)' to '$($f.DestinationPath)' failed: $($error[0].exception.message)"
  }
}

$session.Dispose()

# SSH
$qbiviewIP = "***"
$username = "***"
$secpasswd = ConvertTo-SecureString "***" -AsPlainText -Force
$credential = new-object -typename System.Management.Automation.PSCredential -argumentlist $username, $secpasswd

try {
    # Create ssh connection
    New-SSHSession -ComputerName $qbiviewIP -Credential $credential -ConnectionTimeout 300 -force -ErrorAction Stop -WarningAction silentlyContinue | out-null
    $getSSHSessionId = (Get-SSHSession | Where-Object { $_.Host -eq $qbiviewIP }).SessionId
}
catch {
    Write-Host "Error while connecting to SSH server: $($_.exception.message)"
}

if ($null -ne $getSSHSessionId) {
    # Invoke command
    (Invoke-SSHCommand -SessionId $getSSHSessionId -Command "chmod --changes -R 777 www/public/DesktopScreenshots/").output[0]

    # Terminate connection
    Write-Host ( -join ("Closing SSH connection: ", (Remove-SSHSession -SessionId $getSSHSessionId)))
}

# Date 
$date = Get-Date -Format "HH:mm dd/MM/yyyy"
sql -ins_upd -request "UPDATE last_update_time SET datetime = '$date'"

# Restart script
Start-SleepTimer 180
$arguments = "& '$homeDir\DesktopScreenshots.ps1'"
Start-Process powershell -ArgumentList $arguments -WindowStyle Minimized
Exit
