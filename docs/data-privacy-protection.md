# Data Privacy & Protection

Campaign gives you full control over your contacts. Deleting a contact will completely and permanently remove all of its data and activity from the database.

In addition to any custom fields that you have created and assigned to contacts, the Campaign plugin stores campaign and mailing list activity about contacts, as well as the following data. 

**`email`**  
The contact's email address.

**`country`**  
The country that the contact was last active from (if GeoIP is enabled).

**`geoIp`**  
The GeoIP location that the contact was last active from (if GeoIP is enabled), specifically:  
`continentCode, continentName, countryCode, countryName, regionCode, regionName, city, postCode, timeZone`

**`device`**  
The device that the contact was last active from.

**`os`**  
The OS (operating system) that the contact was last active from.

**`client`**  
The web browser client that the contact was last active from.

**`lastActivity`**  
A timestamp of the contact's last activity.

**`complained`**  
A timestamp of the contact's spam complaint.

**`bounced`**  
A timestamp of the contact's bounced email.

**`verified`**  
A timestamp of the contact's verified email (through double opt-in).