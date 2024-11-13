# Ubuntu Server Configuration - Apache, MySQL, rsyslog, and Filebeat

This guide details the configuration of an Ubuntu Server to work with the ELK Stack for centralized logging. It covers setting up `rsyslog` for sending logs without an agent, configuring Apache and MySQL, and using Filebeat to collect and forward logs to the ELK Stack. Additionally, it includes PHP application setup that generates logs to be captured by Filebeat.

## Table of Contents

- [Configuring rsyslog for Log Forwarding](#configuring-rsyslog-for-log-forwarding)
- [Setting Up Apache](#setting-up-apache)
  - [Deploying PHP Application](#deploying-php-application)
- [Setting Up MySQL](#setting-up-mysql)
  - [Configuring MySQL Logs](#configuring-mysql-logs)
- [Configuring Filebeat](#configuring-filebeat)
  - [Installing Filebeat](#installing-filebeat)
  - [Configuring Filebeat Inputs](#configuring-filebeat-inputs)
  - [Configuring Output to Logstash](#configuring-output-to-logstash)
  - [Enabling Filebeat Modules](#enabling-filebeat-modules)
  - [Setting Up Ingest Pipelines and Dashboards](#setting-up-ingest-pipelines-and-dashboards)
- [Deploying PHP Application Files](#deploying-php-application-files)
- [Troubleshooting](#troubleshooting)

---

## Configuring rsyslog for Log Forwarding

`rsyslog` is the default syslog daemon on Ubuntu, used for log management.

### Step 1: Configure rsyslog to Forward Logs

1. **Edit the rsyslog Configuration File**:
    ```bash
    sudo nano /etc/rsyslog.conf
    ```
   
2. **Enable Remote Logging**:
    - Locate and uncomment (or add) the following lines to enable sending logs to the ELK server:
      ```
      *.* @<ELK_SERVER_IP>:5000
      ```
      - Replace `<ELK_SERVER_IP>` with the IP address of your ELK Stack server.
      - The single `@` symbol uses UDP. For TCP (more reliable), use `@@`:
        ```
        *.* @@<ELK_SERVER_IP>:5000
        ```

3. **Save and Exit**:
    - Press `CTRL + X`, then `Y`, and `Enter` to save changes.

### Step 2: Restart rsyslog

Apply the configuration changes by restarting the rsyslog service:
```bash
sudo systemctl restart rsyslog
```

---

## Setting Up Apache

Apache is a widely used web server. This section covers installing Apache and deploying a PHP application that generates logs.

### Step 1: Install Apache

1. **Update Package Index**:
    ```bash
    sudo apt update
    ```
   
2. **Install Apache**:
    ```bash
    sudo apt install apache2 -y
    ```

3. **Enable and Start Apache Service**:
    ```bash
    sudo systemctl enable apache2
    sudo systemctl start apache2
    ```

4. **Verify Installation**:
    - Open a web browser and navigate to `http://<UBUNTU_SERVER_IP>/`. You should see the Apache2 Ubuntu Default Page.

### Step 2: Install PHP

1. **Install PHP and Required Modules**:
    ```bash
    sudo apt install php libapache2-mod-php php-mysql -y
    ```

2. **Restart Apache to Load PHP Module**:
    ```bash
    sudo systemctl restart apache2
    ```

### Deploying PHP Application

The PHP application generates logs that will be collected by Filebeat.

1. **Clone or Upload PHP Files**:
    - Place your PHP files in the Apache web directory:
      ```bash
      sudo mkdir -p /var/www/html/tarea_app
      sudo chown -R $USER:$USER /var/www/html/tarea_app
      ```
    - Upload your PHP files (`index.php`, `create_task.php`, `functions.php`, `login.php`, `register.php`, `db.php`, `logout.php`) to `/var/www/html/tarea_app/`.

2. **Access the Application**:
    - Navigate to `http://<UBUNTU_SERVER_IP>/` in your web browser.

---

## Setting Up MySQL

MySQL is a relational database management system. This section covers installing MySQL and configuring it to generate necessary logs.

### Step 1: Install MySQL

1. **Install MySQL Server**:
    ```bash
    sudo apt install mysql-server -y
    ```

2. **Secure MySQL Installation**:
    ```bash
    sudo mysql_secure_installation
    ```
    - Follow the prompts to set the root password and secure your MySQL installation.

3. **Enable and Start MySQL Service**:
    ```bash
    sudo systemctl enable mysql
    sudo systemctl start mysql
    ```

### Configuring MySQL Logs

Ensure MySQL is configured to generate the required logs: Error Log, Slow Query Log, and optionally the General Query Log.

#### a. Edit MySQL Configuration

1. **Open MySQL Configuration File**:
    ```bash
    sudo nano /etc/mysql/my.cnf
    ```
    - The path may vary; alternatively, check `/etc/mysql/mysql.conf.d/mysqld.cnf`.

2. **Configure Error Log**:
    - Under the `[mysqld]` section, ensure the error log is set:
      ```ini
      [mysqld]
      log_error = /var/log/mysql/error.log
      ```

3. **Configure Slow Query Log**:
    - Add the following lines under `[mysqld]`:
      ```ini
      slow_query_log = 1
      slow_query_log_file = /var/log/mysql/mysql-slow.log
      long_query_time = 1
      ```

4. **Configure General Query Log (Optional)**:
    - **Warning**: This can generate a large volume of logs and impact performance.
      ```ini
      general_log = 1
      general_log_file = /var/log/mysql/mysql.log
      ```

5. **Save and Exit**:
    - Press `CTRL + X`, then `Y`, and `Enter`.

#### b. Restart MySQL Service

Apply the changes by restarting MySQL:
```bash
sudo systemctl restart mysql
```

### Step 2: Verify MySQL Logs

Ensure that the log files exist and are being updated:
```bash
ls -l /var/log/mysql/
```
- You should see `error.log`, `mysql-slow.log`, and `mysql.log` (if enabled).

---

## Configuring Filebeat

Filebeat is a lightweight shipper for forwarding and centralizing log data.

### Installing Filebeat

1. **Download and Install Filebeat**:

    - **For AMD64 Architecture**:
        ```bash
        curl -L -O https://artifacts.elastic.co/downloads/beats/filebeat/filebeat-7.17.10-amd64.deb
        sudo dpkg -i filebeat-7.17.10-amd64.deb
        ```

    - **For ARM64 Architecture**:
        ```bash
        curl -L -O https://artifacts.elastic.co/downloads/beats/filebeat/filebeat-7.17.10-arm64.deb
        sudo dpkg -i filebeat-7.17.10-arm64.deb
        ```

### Configuring Filebeat Inputs

1. **Edit Filebeat Configuration File**:
    ```bash
    sudo nano /etc/filebeat/filebeat.yml
    ```

2. **Configure Log Inputs**:
    - Specify which logs to collect. For UFW and Login logs:
      ```yaml
      filebeat.inputs:
        - type: log
          enabled: true
          paths:
            - /var/log/ufw.log
          fields:
            log_type: firewall
            event.module: ufw
          fields_under_root: true

        - type: log
          enabled: true
          paths:
            - /var/log/myapp/login.json
          json:
            keys_under_root: true
            overwrite_keys: true
          fields:
            log_type: json
          fields_under_root: true
      ```

3. **Configure Output to Logstash**:
    - Ensure only `output.logstash` is enabled and `output.elasticsearch` is commented out:
      ```yaml
      #output.elasticsearch:
      #  hosts: ["localhost:9200"]

      output.logstash:
        hosts: ["<ELK_SERVER_IP>:5044"]
      ```
      - Replace `<ELK_SERVER_IP>` with your ELK Stack server's IP address.

4. **Save and Exit**:
    - Press `CTRL + X`, then `Y`, and `Enter`.

### Enabling Filebeat Modules

1. **Enable Apache Module**:
    ```bash
    sudo filebeat modules enable apache
    ```

2. **Enable MySQL Module**:
    ```bash
    sudo filebeat modules enable mysql
    ```

3. **(Optional) Enable System Module**:
    - If you want to collect system logs:
      ```bash
      sudo filebeat modules enable system
      ```

### Setting Up Ingest Pipelines and Dashboards

1. **Load Filebeat Index Templates and Pipelines**:
    ```bash
    sudo filebeat setup --pipelines --modules apache,mysql -E output.logstash.enabled=false -E output.elasticsearch.hosts=["<ELK_SERVER_IP>:9200"]
    ```

2. **Load Kibana Dashboards**:
    ```bash
    sudo filebeat setup --dashboards -E output.logstash.enabled=false -E output.elasticsearch.hosts=["<ELK_SERVER_IP>:9200"] -E setup.kibana.host=<ELK_SERVER_IP>:5601
    ```

### Starting and Enabling Filebeat

1. **Start Filebeat Service**:
    ```bash
    sudo systemctl start filebeat
    ```

2. **Enable Filebeat to Start on Boot**:
    ```bash
    sudo systemctl enable filebeat
    ```
---

## Deploying PHP Application Files

Upload your PHP application files to the `tarea_app` directory in the Apache web server.

### PHP Files

- [index.php](./tarea_app/index.php)
- [create_task.php](./tarea_app/create_task.php)
- [functions.php](./tarea_app/functions.php)
- [login.php](./tarea_app/login.php)
- [register.php](./tarea_app/register.php)
- [db.php](./tarea_app/db.php)
- [logout.php](./tarea_app/logout.php)

> **Note**: Ensure these files are placed in `/var/www/html/tarea_app/` and have the appropriate permissions:
> ```bash
> sudo chown -R www-data:www-data /var/www/html/tarea_app
> sudo chmod -R 755 /var/www/html/tarea_app
> ```

---

## Troubleshooting

### Verify rsyslog is Forwarding Logs

1. **Check rsyslog Status**:
    ```bash
    sudo systemctl status rsyslog
    ```

2. **Test Log Forwarding**:
    - Generate a test log:
      ```bash
      logger "Test log message from Ubuntu Server"
      ```
    - Check ELK Stack (Kibana) to see if the log appears.

### Verify Apache is Logging Correctly

1. **Check Apache Logs**:
    ```bash
    sudo tail -f /var/log/apache2/access.log
    sudo tail -f /var/log/apache2/error.log
    ```

2. **Access the PHP Application**:
    - Navigate to `http://<UBUNTU_SERVER_IP>/tarea_app/` and perform actions to generate logs.

### Verify MySQL is Logging Correctly

1. **Check MySQL Logs**:
    ```bash
    sudo tail -f /var/log/mysql/error.log
    sudo tail -f /var/log/mysql/mysql-slow.log
    sudo tail -f /var/log/mysql/mysql.log  # If General Log is enabled
    ```

### Verify Filebeat is Running and Sending Logs

1. **Check Filebeat Service Status**:
    ```bash
    sudo systemctl status filebeat
    ```

2. **Check Filebeat Logs**:
    ```bash
    sudo tail -f /var/log/filebeat/filebeat.log
    ```

3. **Test Connectivity to ELK Server**:
    ```bash
    sudo filebeat test output
    ```

### Verify Logs in ELK Stack

1. **Access Kibana**:
    - Navigate to `http://<ELK_SERVER_IP>:5601/` in your web browser.

2. **Check Index Patterns**:
    - Ensure Filebeat indices are present (e.g., `filebeat-*`).

3. **View Dashboards**:
    - Go to the Dashboards section to view the imported dashboards for Apache and MySQL.

### Common Issues

- **Filebeat Not Sending Logs**:
    - Ensure the `filebeat.yml` output section is correctly configured to point to Logstash.
    - Verify network connectivity between the Ubuntu server and ELK server.
    - Check Filebeat logs for errors.

- **Logs Not Appearing in Kibana**:
    - Ensure that Logstash is properly configured and running.
    - Check Logstash logs for any processing errors.
    - Verify that the correct index patterns are used in Kibana.

- **Permission Issues**:
    - Ensure that Filebeat has read access to the log files:
      ```bash
      sudo chmod -R 755 /var/log/apache2/
      sudo chmod -R 755 /var/log/mysql/
      sudo chmod -R 755 /var/log/myapp/
      ```

- **Elasticsearch Indexing Issues**:
    - Verify Elasticsearch is running and accessible.
    - Check Elasticsearch logs for any errors related to indexing.

---

This completes the Ubuntu Server configuration guide for integrating with the ELK Stack. Ensure all steps are followed accurately and refer to the troubleshooting section if you encounter any issues. Proceed to configure other servers and Kibana alerts as needed.
