package main

import (
	"fmt"
	"log"
)

var terms = make(map[string]int)

func PopulateTerms() map[string]int {
	// taxonomy := "hsn-sac-finders"
	// fmt.Println(fmt.Sprintf("SELECT distinct(wp_terms.term_id) FROM `wp_term_taxonomy` join wp_terms on wp_term_taxonomy.term_id = wp_terms.term_id Where slug=%v", slug))
	rows, err := Db.Query("SELECT term_id,slug FROM wp_terms")
	if err != nil {
		fmt.Println("Taxonomy In")
		log.Fatal(err)
	}
	defer rows.Close()
	var termID int
	var termSlug string
	for rows.Next() {
		rows.Scan(&termID, &termSlug)
		terms[termSlug] = termID
	}
	// fmt.Println(terms)
	return terms
}

func GetTermID(slug string) int {
	termID, isset := terms[slug]
	if !isset {
		return 0
	}
	return termID
}
