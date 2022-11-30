# kraSigner
## Agreed Project scope: 

- Integrate Unleashed API to ESD;
- Send the pdf invoice for ESD signing;
- Send the Electronically signed PDF invoices via email to a recipient.

## What has been delivered in alignment with the scope/project objective:

- Fetch sales invoices from Unleashed API.
- Check if invoice has ever been signed (data from Mysql DB)
- Send each invoice for signing to ESD API.
- Generate a QRCode for the KRA signed invoice.
- Generate an HTML template for the signed invoice (Out of scope).
- Convert HTML invoice to PDF file.
- Track invoice status via a Mysql DB (Out of scope).
- Send email and a signed invoice PDF attachment to recipients.

## Access server or AWS environment or your preference
1. SSH
```
ssh -i yourkey.pem serverusername@server-ip
```
2. If you have .ppk, convert by using putty-tools
```
apt-get install putty-tools
puttygen <the_key.ppk> -O private-openssh -o <new_openssh_key>.key
```
## Pre-requisites:
1. Install mysql
```
sudo apt-get update
sudo apt-get upgrade
sudo apt-get purge mysql-server mysql-client
sudo apt-get install mysql-server mysql-client
```
2. Install PHP >= 7
3. Install Composer 

## Installation:
0. Clone from this repo into your prefered server location:
```
git clone https://github.com/cmigayi/kraSigner.git
```
1. Update php composer. Install composer if not available. 
```
composer update
```
2. Load composer to generate relevant files and libraries.
```
composer dump-autoload -o
```
3. Create the following directories in project root:
    - invoices
    - logs
    - tmp
4. Add "Write" permission to each directory
```
sudo chmod o+w invoices logs tmp
``` 	
5. You will need a Config.php with these details:
```
<?php
return [
	"host" => "localhost",
	"username" => "",
	"password" => "",
	"database" => "",

	"unleashed_api" => "",
	"unleashed_api_id" => "",
	"unleashed_api_key" => "",

	"esd_api" => "",

	"smtp_server" => "",
	"email_username" => "",
	"email_password" => "",
	"port" => 587,
	"from" => "",
];
```
6. Create DB in Mysql, then import the .sql file
```
mysql -u user -p databasename < databasename.sql
```  
7. In case of any issues, you can check the server logs. Here is a location for Apache2 logs:
```
sudo ls /var/log/apache2/
```
8. In case there is an issue with SSL, (Note this issue might not produce errors or exception), add the following to curl headers:
```
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
``` 
9. Setting up Scheduler on ubuntu
- Tutorial used -> [Ubuntu crontab tutorial](https://linuxhint.com/run_cron_job_every_minute/)
- [x] Enable crontab on Ubuntu
```
crontab -e
```
- [ ] Add task to be executed at the bottom of the editor. Use bash(.sh).
```
* * * * * /bin/krasigner_shedulers/springvalleycoffee.sh
```
	- The bash script above will be executed in an interval of 1 minute (* * * * *).
- [ ] Create the bash file in the location above (/bin/krasigner_shedulers/), or prefered location.
- [ ] Then add the script below:
```
#!/bin/bash
wget -q -O /dev/null '{BaseURL}/kraSigner/kraSigner/index.php' > /dev/null 2>&1
```
- [ ] Make the bash file executable as show below:
```
sudo chmod +x /bin/krasigner_shedulers/springvalleycoffee.sh
```
- [ ] Check crontab by list
```
crontab -l
```
 
10. Printing A4 (Portrait or Landscape)
- Tutorial used -> [Paper CSS doc and repo page](https://github.com/cognitom/paper-css/blob/master/examples/multiple-sheets.html).

### Devise goes out of service or remains busy after a request (http error 503)
- Signing device goes out of service or remains busy after one request, error 503
The device goes out of service because the next invoice request is sent before the device responds to the previous request.
To solve this issue, we re-architectured as follows:
- Redis queue to ensure the device handles one request at a time  
- Two schedulers: one updates the queue every minute; while the other signs the invoices from the queue (one at a time) every 1 minute. 
 - Install redis on ubuntu:
 ```
 sudo apt install -y php-redis
 ```
 - Find the Redis password in the config file
 ```
 sudo nano /etc/redis/redis.conf
 ```
 - Locate password here:
 ```
 requirepass YOURPASSPHRASE
 ```
 - To start / stop / restart redis, use this:
 ```
 sudo systemctl restart redis
 ```
 - To execute Redis commands, execute Redis-ctl in the Ubuntu Terminal:
 ```
 redis-cli
 ``` 
 - To authorize Redis with password:
 ```
 auth [enter password]
 ``` 
 - Clear all Redis data:
 ```
 flushall
 ```
 - Tutorial1 used -> [Redis implementation](https://www.vultr.com/docs/implement-redis-queue-and-worker-with-php-on-ubuntu-20-04/)

 - Tutorial2 used -> [Redis password tuts](https://onelinerhub.com/php-redis/using-password-to-connect-to-redis)
### Architecture
![architecture](/architectural-design.png?raw=true "Architecture")
