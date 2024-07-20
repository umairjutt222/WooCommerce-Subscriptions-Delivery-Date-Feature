# WooCommerce-Subscriptions-Delivery-Date-Feature

Task: Develop a Custom WordPress Plugin

Objective: WooCommerce Subscriptions Delivery Date Feature

Create a simple plugin that adds a “Delivery Date” field to both simple and variable subscription products. This field will allow admins to set a recurring delivery date (e.g., the 3rd day of every 2 weeks or 1st day of every month). The next 3 recurring dates should be displayed on the product page in a drop-down menu. The selected date should be displayed and editable in the cart page.

Explanation:
This plugin will add following functionality to the WooCommerce Subscriptions plugin
=> Add a new field called “Delivery Date” in the product editor for simple subscription products and each variation of variable subscription products
=> The field should allow admins/shop managers to choose a recurring period (e.g., every 3rd day of every 2 weeks, or every 1st day of every month, or 1st day of every 6th month).
=> The calculate_next_delivery_dates and parse_delivery_interval methods now handle patterns for "every X day of every Y week/month" as well as "X day of every month/week".
=> Display the Next three Reaccuring date on product page 
=> Display the next reaccuring date on cart page
