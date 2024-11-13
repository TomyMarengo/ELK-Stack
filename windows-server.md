# Windows Server Configuration - Sending Logs to ELK Stack

This guide explains how to configure a Windows Server to send event logs to an ELK stack using two methods: without an agent using Windows Event Forwarding (WEF) and with an agent using Winlogbeat.

## Table of Contents
- [Sending Logs Without an Agent](#sending-logs-without-an-agent)
  - [Configuring Windows Event Forwarding (WEF)](#configuring-windows-event-forwarding-wef)
- [Sending Logs With an Agent](#sending-logs-with-an-agent)
  - [Downloading and Installing Winlogbeat](#downloading-and-installing-winlogbeat)
  - [Configuring Winlogbeat](#configuring-winlogbeat)
  - [Installing and Starting Winlogbeat Service](#installing-and-starting-winlogbeat-service)
- [Troubleshooting](#troubleshooting)

---

## Sending Logs Without an Agent

### Configuring Windows Event Forwarding (WEF)

1. **Overview**: Windows Event Forwarding (WEF) enables Windows systems to send events to a designated Windows server configured as a collector.
2. **Requirements**: WEF requires a collector server to receive and forward logs. Note that without an agent, it is challenging to send logs directly to a non-Windows ELK server.

#### Limitations

Using WEF is suitable in environments with multiple Windows machines and a dedicated Windows collector server. However, for mixed environments, using an agent like Winlogbeat is generally more practical.

---

## Sending Logs With an Agent

### Step 1: Open PowerShell as Administrator

Open PowerShell with elevated permissions by using:

```plaintext
runas /user:Administrator powershell
```

### Step 2: Downloading and Installing Winlogbeat

1. **Download Winlogbeat:** Use the following PowerShell command to download Winlogbeat:

```powershell
$ProgressPreference = 'SilentlyContinue'
Invoke-WebRequest -Uri https://artifacts.elastic.co/downloads/beats/winlogbeat/winlogbeat-7.17.10-windows-x86_64.zip -OutFile C:\winlogbeat.zip
```

This will download the Winlogbeat ZIP file to `C:\winlogbeat.zip`.

2. **Extract the ZIP File:** Use PowerShell to extract the ZIP file:

```powershell
Expand-Archive -Path 'C:\winlogbeat.zip' -DestinationPath 'C:\winlogbeat'
```

### Step 3: Configuring Winlogbeat

1. **Navigate to Winlogbeat Directory:**

```powershell
cd 'C:\winlogbeat\winlogbeat-7.17.10-windows-x86_64'
```

2. **Edit the `winlogbeat.yml` File:** Open `winlogbeat.yml` in a text editor:


```powershell
notepad winlogbeat.yml
```

3. **Configure Output to Logstash:** Ensure the `output.logstash` section is enabled and the `output.elasticsearch` section is commented out:

```yaml
winlogbeat.event_logs:
  - name: Application
  - name: System
  - name: Security

#output.elasticsearch:
#  hosts: ["localhost:9200"]

output.logstash:
  hosts: ["<ELK_SERVER_IP>:5044"]
```

### Step 4: Installing and Starting Winlogbeat Service

1. **Install Winlogbeat as a Windows Service:** Run the installation script:

```powershell
.\install-service-winlogbeat.ps1
```

If script execution is restricted, set the policy temporarily:

```powershell
Set-ExecutionPolicy -ExecutionPolicy Unrestricted -Scope Process
```

After installation, you can revert the policy:

```powershell
Set-ExecutionPolicy -ExecutionPolicy Restricted -Scope Process
```

2. **Start the Winlogbeat Service:**

- Test configuration:

```powershell
.\winlogbeat.exe test config -e
```

- Start the service:

```powershell
.\winlogbeat.exe install service
Start-Service winlogbeat
```

3. **Verify the Service Status:**

```powershell
Get-Service winlogbeat
```

This should display that the Winlogbeat service is `Running`.

### Troubleshooting

#### Test Connectivity

**To verify connectivity with the ELK server:**

```powershell
Test-NetConnection -ComputerName <ELK_SERVER_IP> -Port 5044
```

#### Check Winlogbeat Logs

Winlogbeat logs are located at `C:\ProgramData\winlogbeat\Logs\winlogbeat`. To view the latest logs:

```powershell
Get-Content 'C:\ProgramData\winlogbeat\Logs\winlogbeat' -Tail 50
```

This will display the last 50 lines of the log file.
