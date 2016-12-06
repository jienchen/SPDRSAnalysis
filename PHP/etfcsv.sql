USE etfcsv;

CREATE TABLE IF NOT EXISTS $ticker
(
  id              int unsigned NOT NULL auto_increment,
  type  		  varchar(1) NOT NULL,
  name			  varchar(20) NOT NULL,
  percentage	  DECIMAL(6,2) NOT NULL,
  shares          DOUBLE unsigned NOT NULL,

  PRIMARY KEY     (id)
);