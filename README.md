# NZTA Security Development Lifecycle Tool :: Framework

The SDLT is Web Application that supports, and expedites I.T. security professionals as part of the change approval process within their organisation.

## Requirements

The SDLT is written in ReactJS and PHP and built on the [SilverStripe](https://silverstripe.org) framework. 

This repository is the PHP component of [NZTA's SDLT project](https://github.com/nzta/sdlt) and intended to be checked out as a dependency. View that project for setup instructions.

## Migrations
As of 3.2.0, users migrating to this version will need to set the CISO and Security Architect groups in the SiteConfig before using SDLT to receive emails. Previous versions automatically defined this, and they will stop working if the group name is changed.
