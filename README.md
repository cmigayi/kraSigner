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
