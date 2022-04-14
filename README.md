# CSVParser


<h1>Parse CSV into an 2D array </h1>

This class can handle line feed in an enclosed data. Which can not be done using PHP ``` fgetcsv() ``` or ```str_getcsv```

An example of CSV data:
```
"a,b,c",
data 1,"data with multiline:
line1
line2"
```

Quick start:

```
$uri = './data.csv';
$parser = new CSVParser()
$result = $parser->parse($uri);  // 2D array
```
No dependency, only PHP
