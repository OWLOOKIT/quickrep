QuickRep Reporting Engine
========

A PHP reporting engine that works especially well with Laravel, built with love at [Owlookit Systems](https://owlookit.com)


Architecture
------------------

![Quickrep Data Flow Diagram](https://raw.githubusercontent.com/Owlookit/Quickrep/master/documentation/Quickrep_Reporting_Engine_Design.png)

Basically the way Quickrep works is to run your SQL against your data... then put it into a cache table (usually in the \_quickrep database)
Then it does its paging and sorting against that cached version of your data.  
