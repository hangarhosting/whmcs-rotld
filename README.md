# whmcs-rotld
 ROTLD registrar module for WHMCS
 
 Developed with passion in https://hangar.hosting labs, based on standard documentation from https://developers.whmcs.com/domain-registrars/
 
 Please note that you must be a registered and active ROTLD partner in order to
 use this module.
 Find more details and register at https://rotld.ro/partnership/

 
### WARNING!  this code is still work in progress!
 Although tested, the module is not yet ready for production.
 It has basic functionalities, but still requires a lot of work.

#### What is working
- installation
- configuration (including test mode toggle)
- following commands have been tested and seem to work OK
	- `RenewDomain`
	- `GetNameservers`
	- `SaveNameservers`
	- `GetContactDetails`
	- `RegisterNameserver`
	- `ModifyNameserver`
	- `DeleteNameserver`
	- `Sync`



### Installation

#### Prerequisites
We assume that you already are a ROTLD partner and 
you have all necessary credentials at hand.

If you are not yet a partner, please stop this installation and go to
https://rotld.ro/partnership/. Finish the partnership process and come back.

#### Installation procedure
1. Connect to your WHMCS instance via SSH
2. Clone the repository in a temporary folder (use any folder suits you)
```
cd /tmp
git clone https://github.com/hangarhosting/whmcs-rotld.git
```
3. Copy the content of `whmcs-rotld` folder into your WHMCS instance (folders `modules` and `widgets`)
4. Browse to your WHMCS admin dashboard
5. Go to *System settings* > *Domain Registrars*
6. Look for ROTLD registrar and click *Activate*
7. After activation, click *Configure* and enter the required info
8. Go to *Domain Pricing* and for all romanian TLDs (.ro, .com.ro, .org.ro, .tm.ro, etc) choose **Rotld** as registrar in the last column - *Auto Registration*.
