# scheduler
Scheduling time for meetings at major events.

This program was created to organize the schedule short meetings between a group of suppliers and a group of consumers of the goods or services.
For convenience, the original data is downloaded as a CSV file table and discharged in the form of a CSV file table but already filled schedule.

The first and second rows in the table are services. The first line contains the name of the company, and the second line indicates the number of persons participating in the meeting of the company.
The first three columns in the table are also services. 
At the intersection of the row and column markers indicate if the counterparties must meet, and if you want to ask yourself the exact time they enter the marker instead of the exact time of the meeting.

The input table should look something like this:

|Company|Type |User	|Comp1|Comp2|
|       |     |     |    1|    2|
|group1	|rest |ana	|12:00|X    |
|group1	|rest |ivan	|     |11:00|
|group2	|rest |yan	|10:00|     |
|group2	|rest |elen	|X	  |X    |
|group3	|hotel|olga	|     |X    |
|group3	|hotel|semen|     |X    |
|group4	|hotel|rosti|X	  |     |

The program allows you to specify the following schedule settings:
Slot duration in minutes
Event Start Time HH:MM
End Time events HH:MM
Possible pauses separated by commas

The output table as a result will look like this:

|Company|Type |User	|Comp1|Comp2|
|       |     |     |    1|    2|
|group1	|rest |ana	|12:00|10:00|
|group1	|rest |ivan	|     |11:00|
|group2	|rest |yan	|10:00|     |
|group2	|rest |elen	|10:15|10:30|
|group3	|hotel|olga	|     |10:15|
|group3	|hotel|semen|     |10:45|
|group4	|hotel|rosti|10:30|     |

