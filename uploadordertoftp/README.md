# UploadOrderToFTP PrestaShop module

My client wanted to process PrestaShop orders using their Sage ERP X3 (Adonix X3). 
For that to happens they needed to upload a text file with a required format to 
an external FTP server. 

Based in the mailalerts module I developed this small module to upload the order 
files to the FTP. There is still room for it to be improved but till now has 
been working for over four months doing the required job.

## Setup

### Installation

1. Copy the uploadordertoftp folder to your modules folder.
2. Log into your PrestaShop dashboard.
3. Hover over the Modules tab and select the Modules option.
4. Search for **Upload order to FTP** and click Install.

### Configuration

* You have to fill the connection data to the server (address, username and password).
* You can set as well the path where the file will be saved in the server.
* I added the option to set the passive mode on if the client is behind a firewall.

### Customization

The main idea is that the hookActionValidateOrder function gets all the parameters 
of the order into smarty variables. So you can just change the format of the order 
template to suit your needs.


## To-Do

In this particular case the client needed to calculate the percentage of the 
discounts and show the prices excluding taxes. So I have to work in a more standard 
version or even configurable one.