# bot-telegram-php
 Bot de telegram que lleva las cuentas de los gastos y devuelve la suma total segun las fechas especificadas
 Este Bot funciona para cualquier persona que lo quiera usar https://t.me/cuentashogar_bot
 
 ## Como funciona:

* Este bot tiene 2 comandos:

1. /c <valor> EJEMPLO: /c 100
 Una vez se ingresa este valor queda registrado en la base de datos con tu chat_id (Puedes registrar tantos datos como desees)
 Cuando un usuario registra un valor automaticamente queda guardada la fecha.
 
 2. /sumar <fecha_inicio> <fecha_final> EJEMPLO: /sumar 2023-01-01 2023-12-31
Es decir que el formato de las fechas es AAAA-MM-DD este comando realiza una busqueda en la base de datos y te devuelve la sumatoria de todos los valores en las fechas especificadas
 
 ### A llevar las cuentas!!!!
