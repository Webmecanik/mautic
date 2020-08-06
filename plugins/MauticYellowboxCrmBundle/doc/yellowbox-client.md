# Using Yellowbox client

### Tables

leads
contact
societe

### Hooks

https://yellowbox9.mydimo-crm.fr/demowb/ws/

https://yellowbox9.mydimo-crm.fr/demowb/ws/authentication
https://yellowbox9.mydimo-crm.fr/demowb/ws/table

Get Fields
https://yellowbox9.mydimo-crm.fr/demowb/ws/field/query?idTable=5962

Save contact
https://yellowbox9.mydimo-crm.fr/demowb/ws/record/v2
{
  "record": {
    "table": {
      "id": "5962",
      "name": "c5962leads"
    },
    "values": [
      {
        "field": {
        "dbName": "C8249EMAIL",
        "fieldType": "ALPHANUMERIQUE",
        "id": "8249",
        "keywordListId": "0",
        "name": "Email"
        },
        "value": "kuzmany1@gmail.com"
      }
       
    ]
  },
  "typeImport": 0
}