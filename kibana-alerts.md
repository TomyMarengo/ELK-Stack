# Alert Creation in Kibana

## Table of Contents
- [Introduction](#introduction)
- [Prerequisites](#prerequisites)
- [Alert 1: Missing "healthcheck" Logs for More Than 1 Minute](#alert-1-missing-healthcheck-logs-for-more-than-1-minute)
    - [Create a Rule](#create-a-rule)
    - [Define the Alert Condition](#define-the-alert-condition)
    - [Set Alert Actions](#set-alert-actions)
    - [Testing the Apache Healthcheck Alert in Kibana](#testing-the-apache-healthcheck-alert-in-kibana)
- [Alert 2: User Login from an unusual IP](#alert-2-user-login-from-an-unusual-ip)
    - [Create a Rule](#create-a-rule-1)
    - [Define the Alert Condition](#define-the-alert-condition-1)
    - [Set Alert Actions](#set-alert-actions-1)
    - [Testing the Unusual IP Alert in Kibana](#testing-the-unusual-ip-alert-in-kibana)
- [Alert 3: UFW Blocked Connections - Firewall Attack Detection](#alert-3-ufw-blocked-connections---firewall-attack-detection)
    - [Create a Rule](#create-a-rule-2)
    - [Define the Alert Condition](#define-the-alert-condition-2)
    - [Set Alert Actions](#set-alert-actions-2)
    - [Testing the UFW Blocked Connection Alert](#testing-the-ufw-blocked-connection-alert)

## Introduction

Kibana is a powerful visualization tool that allows you to explore, visualize, and monitor your Elasticsearch data. It provides various features, such as dashboards, discovery, and alerting, which are essential for proactive monitoring. In this guide, we will cover how to set up three different types of alerts in Kibana to monitor your Apache logs:
- **Alert 1**: Missing "healthcheck" logs for more than 1 minute.
- **Alert 2**: Too many 4xx errors in the last 5 minutes.
- **Alert 3**: Failed login attempts from a single IP.

Each alert helps monitor different aspects of your server, ensuring your application runs smoothly and securely.

## Prerequisites

Before you begin, ensure that:
- Kibana and Elasticsearch are set up and running.
- Apache logs are ingested into Elasticsearch and are accessible from Kibana.
- You have basic knowledge of Kibana's interface, particularly the Alerts and Actions feature.

---

## Alert 1: Missing "healthcheck" Logs for More Than 1 Minute
The **Healthcheck Alert** in Kibana is designed to monitor Apache server logs for missing healthcheck requests. This alert triggers when the Apache server does not send a healthcheck log within a specified time window (e.g., 1 minute). It is useful for detecting if the server has become unresponsive or if there are issues with healthcheck logging, which could indicate server downtime or other problems.

This alert helps ensure that any disruptions in server health checks are detected promptly, allowing for proactive monitoring and troubleshooting.

### Create a Rule

1. In Kibana, go to **Rules and Connectors**.
2. Click **Create Rule**.
3. Complete the following fields:
    - **Name**: `Apache Healthcheck`
    - **Check every**: `30 seconds`
    - **Notify**: `Every time alert is active`

4. Select the rule type as **Log threshold**.
5. Choose the index pattern: `filebeat-*`.

### Define the Alert Condition

For the condition, we’ll use a query that checks if the `ELB-HealthChecker/2.0` user agent does not log an entry within the last minute. Complete the query as follows:
- **WHEN THE count OF LOG ENTRIES**
- **WITH `user_agent.original`**
- **IS `ELB-HealthChecker/2.0`**
- **IS less than 1**
- **FOR THE LAST 1 minute**
- **GROUP BY**: `Nothing (ungrouped)`

This will trigger the alert if no healthcheck logs from `ELB-HealthChecker/2.0` are found in the last minute.

### Set Alert Actions

1. Create a connector for the action that will be triggered when the alert is fired. In this case, we'll create an **Index** connector. If you had the necessary license, you could also configure other types of connectors like **Webhook** or **Email**.

2. Configure the **connector**:
    - **Connector name**: `healthcheck-connector`
    - **Index**: `.alerts-observability.logs.alerts-default`
    - **Run when**: `Fired`
    - **Document to index**:

```json
{
  "message": "Alert triggered for rule {{rule.name}}. Apache server is down",
  "rule_name": "{{rule.name}}"
}
```

### Testing the Apache Healthcheck Alert in Kibana

After setting up the alert for missing "healthcheck" logs from Apache, it's important to test whether the alert works as expected. To do this, we will simulate the scenario where the Apache server stops sending healthcheck logs, which will trigger the alert. Follow the steps below to test the alert:
1. Stop the Apache server to simulate the missing healthcheck logs:
   ```bash
   sudo systemctl stop apache2
    ```
2. In Kibana, go to the Discover section.
3. In the Indexes panel, click on the index: .alerts-observability.logs.alerts-default.
4. In the search bar, filter by rule_name: "Apache healthcheck" to find the triggered alert.
5. You should see an entry indicating that the alert was triggered. The message should look like this:
    ```
    Alert triggered for rule Apache Healthcheck. Apache server is down
    ```

## Alert 2: User Login from an unusual IP
This alert is triggered when a user logs in from a different IP address than before. This can help detect potentially suspicious login activity or unauthorized access attempts. For this example, we'll set up an alert that checks when a user logs in from a new IP address.

### Create a Rule

1. In Kibana, go to **Rules and Connectors**.
2. Click **Create Rule**.
3. Complete the following fields:
    - **Name**: `User Login from Unusual IP`
    - **Check every**: `1 minute`
    - **Notify**: `Every time alert is active`

4. Select the rule type as **Elasticsearch Query**.
5. Choose the appropriate index pattern for your login logs (e.g., `filebeat-*`).

For the condition, we’ll write an Elasticsearch query that checks for multiple login attempts from different IPs for the same user. The query is designed to match scenarios where a user logs in from more than one IP address in the last minute, using the `email` and `ip_address` fields:
```json
{
  "query": {
    "match_all": {} 
  },
   "size": 0,
  "aggs": {
    "emails": {
      "terms": {
        "field": "email.keyword",
        "size": 10000
      },
      "aggs": {
        "unique_ips": {
          "cardinality": {
            "field": "ip_address.keyword"
          }
        }
      }
    },
    "emails_with_multiple_ips": {
      "bucket_selector": {
        "buckets_path": {
          "uniqueIpCount": "emails>unique_ips.value"
        },
        "script": "params.uniqueIpCount > 1"
      }
    }
  }
}
```
It checks if the email field has multiple associated ip_address values for the same user.

### Define the Alert Actions

1. Create a connector for the action that will be triggered when the alert is fired. In this case, we’ll create an Index connector (you can also use Webhook or Email if your license allows).
2. Configure the connector:
    - **Connector name**: `login-ip-connector`
    - **Index**: `.alerts-observability.logs.alerts-default`
    - **Run when**: `Fired`
    - **Document to index**:

```json
{
  "message": "Alert triggered for rule {{rule.name}}. User {{context.results[0].email}} logged in from multiple IPs",
  "rule_name": "{{rule.name}}"
}
```
### Testing the Unusual IP Alert in Kibana
To test the alert, you can simulate a user logging in from different IP addresses within a short time frame. Follow these steps:
1. Log in to http://<PUBLIC_IP>/tarea_app/login.php from two different IP addresses within a minute.
2. In Kibana, go to the Discover section.
3. In the Indexes panel, click on the index: .alerts-observability.logs.alerts-default.
4. Filter by rule_name: "User Login from Unusual IP" to find the triggered alert.
5. You should see an entry indicating that the alert was triggered.

## Alert 3: UFW Blocked Connections - Firewall Attack Detection

This alert is triggered when a UFW (Uncomplicated Firewall) log entry indicates that a connection attempt was blocked. This can help detect potential attack attempts on a server. The alert looks for log entries that contain the string "BLOCK" in the `message` field of UFW logs.

### Create a Rule

1. In Kibana, go to **Rules and Connectors**.
2. Click **Create Rule**.
3. Complete the following fields:
   - **Name**: `UFW Blocked Connection`
   - **Check every**: `1 minute`
   - **Notify**: `Every time alert is active`

4. Select the rule type as **Elasticsearch Query**.
5. Choose the appropriate index pattern for your UFW logs (e.g., `filebeat-*`).
6. Define the query to match log entries with the string "BLOCK" in the `message` field:
```json
{
  "query": {
    "match": {
      "message": "*BLOCK*"
    }
  }
}
```
The query looks for entries in the message field that contain the string "BLOCK", which is part of UFW log messages indicating that an incoming connection has been blocked.

### Define the Alert Actions
1. Create a connector for the action that will be triggered when the alert is fired. You can create an Index connector (or use Webhook or Email if your license allows).

2. Configure the connector:
    - **Connector name**: `ufw-block-connector`
    - **Index**: `.alerts-observability.logs.alerts-default`
    - **Run when**: `Fired`
    - **Document to index**:

```json
{
  "message": "Alert triggered for rule {{rule.name}}. UFW blocked a connection attempt",
  "rule_name": "{{rule.name}}"
}
```

### Testing the UFW Blocked Connection Alert

1. Trigger a UFW block by attempting to connect to the server from a blocked IP (you can use a VPN or an external IP).
2. Wait for the alert condition to be met (within 1 minute).
3. Go to Discover in Kibana and search for the index .alerts-observability.logs.alerts-default.
4. Filter by rule_name: "UFW Blocked Connection" to view the triggered alert logs.
5. Verify that the logs with the string "BLOCK" in the message field appear, indicating that the alert was successfully triggered.
