# Scheduler

##Description

Scheduling time for meetings at major events.

This program was created to organize the schedule short meetings between a group of suppliers and a group of consumers of the goods or services.
For convenience, the original data is loaded as a CSV file.
And output in html-table or discharged as a CSV file, but with a filled schedule.

The first and second rows in the table are services. The first line contains the name of the company, and the second line indicates the number of persons participating in the meeting of the company.
The first three columns in the table are also services. 
At the intersection of the row and column markers indicate if the counterparties must meet, and if you want to ask yourself the exact time they enter the marker instead of the exact time of the meeting.

The program allows you to specify the following schedule settings:
Slot duration in minutes
Event Start Time HH:MM
End Time events HH:MM
Possible pauses separated by commas

See example init table in file example.csv

##Demo

http://selikoff.ru/scheduler/index.php
