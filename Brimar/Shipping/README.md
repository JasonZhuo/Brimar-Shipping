# Brimar Shipping Module for Magento 2

## Features
- Custom shipping method with fixed base price
- Optional add-ons: Residential Delivery and Scheduled Delivery
- Admin configuration for base price and method name
- Order details integration

## Installation
1. Copy files to `app/code/Brimar/Shipping`
2. Run commands:
```bash
bin/magento module:enable Brimar_Shipping
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush

## Configuration
1. Navigate to Magento Admin:
   - Go to **Stores** → **Configuration** → **Sales** → **Delivery Methods**

2. Brimar Shipping Settings:
   - **Enable**: Yes/No
   - **Method Title**: (Displayed at checkout)
   - **Carrier Name**: (Shipping carrier name)
   - **Base Price**: (Default shipping cost)
   - **Surcharges**:
     - Residential Delivery: [amount]
     - Scheduled Delivery: [amount]