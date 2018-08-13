package main

import (
	"database/sql"
	"log"

	_ "github.com/go-sql-driver/mysql"
)

var Db *sql.DB
var err error

func Connect() {
	Db, err = sql.Open("mysql", "root:root@/gsthero_live")
	if err != nil {
		log.Fatalln(err)
	}
}

func Close() {
	err = Db.Close()
	if err != nil {
		log.Fatalln(err)
	}
}
