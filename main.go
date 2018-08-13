package main

import (
	"bufio"
	"database/sql"
	"encoding/csv"
	"fmt"
	"io"
	"log"
	"os"
	"os/exec"
	"strconv"
	"strings"
	"sync"
	"time"

	"github.com/avelino/slugify"
)

var stmtIns *sql.Stmt
var SuccessPost int
var FaildPost int

func main() {
	Connect()
	PopulateTerms()
	csvFile, _ := os.Open("csv_assets/sac-code-v2.csv")
	reader := csv.NewReader(bufio.NewReader(csvFile))
	var posts []map[string]string
	var wg sync.WaitGroup
	var startTime = time.Now()
	Allline, error := reader.ReadAll()
	for _, line := range Allline {

		if error == io.EOF {
			break
		} else if error != nil {
			log.Fatal(error)
		}
		// keys := [7]string{"type", "code", "content", "hsn_code_4_digit", "chapter_no", "sch", "gst_rate"}
		keys := [6]string{"type", "code", "content", "effective_from", "also_check", "gst_rate"}

		post := make(map[string]string)
		if len(line) != 6 {
			fmt.Println("Not matching column header name array")
			continue
		}
		for index, key := range keys {
			post[key] = line[index]
		}
		posts = append(posts, post)
	}
	size := len(posts)
	chunkSize := size / 70

	if (size % 70) > 0 {
		chunkSize++
	}
	wpCount := size / chunkSize
	if (size % chunkSize) > 0 {
		wpCount++
	}
	wg.Add(wpCount)
	fmt.Println(size)
	fmt.Println(chunkSize)
	fmt.Println(wpCount)
	for i := 0; i < wpCount; i++ {
		start := (i * chunkSize)
		end := ((i + 1) * chunkSize)
		if end > size {
			end = size
		}
		go func(tempPost []map[string]string) {
			for _, post := range tempPost {
				postDataWp(post)
			}
			wg.Done()
		}(posts[start:end])
	}
	wg.Wait()

	fmt.Printf("Success Posts : %v \n", SuccessPost)
	fmt.Printf("Failed Posts : %v \n", FaildPost)
	fmt.Println("\nTime consumed: ", time.Since(startTime))

	if stmtIns != nil {
		defer stmtIns.Close()
	}
}

func postDataWp(post map[string]string) {

	//Create post into wp_posts table
	content := strings.Replace(post["content"], `"`, `\"`, -1)
	if post["code"] != "" && content != "" {
		cmd := exec.Command("bash", "-c", "php add_custom_post.php -t '"+post["code"]+"' -c \""+content+"\" -e abcd -u admin -p pere@123 -s publish --ty hsn-sac-finder")
		result, err := cmd.Output()
		if err != nil {
			fmt.Println("Post In")
			log.Println(err)
		}
		postID, _ := strconv.Atoi(string(result))
		if postID == 0 {
			fmt.Println(content)
		}

		log.Printf("Command finished Post Id %v", postID)
		if postID > 0 {
			SuccessPost++
			//Attach taxonomy to current post
			termType, isset := post["type"]
			if isset {
				taxonomy := "hsn-sac-finders"
				slug := slugify.Slugify(termType)
				var termID int
				termID = GetTermID(slug)
				if termID > 0 {
					cmd := exec.Command("bash", "-c", fmt.Sprintf("php add_term_taxonomy_to_post.php --id %v --tm '%v' --tx '%v'", postID, slug, taxonomy))
					_, err := cmd.Output()
					if err != nil {
						FaildPost++
						fmt.Println("Taxonomy In Insert")
						log.Println(err)
					}
				}
				// dataYoInsert := [4]string{"hsn_code_4_digit", "chapter_no", "sch", "gst_rate"}
				dataYoInsert := [4]string{"effective_from", "also_check", "gst_rate"}
				metaStatement()
				for _, metaKey := range dataYoInsert {
					metaValue := strings.Trim(post[metaKey], " ")
					if metaValue == "" || metaValue == "--" || metaValue == "---" || strings.ToUpper(metaValue) == "NIL" {
						metaValue = ""
					}
					_, err := stmtIns.Exec(postID, metaKey, metaValue)
					if err != nil {
						FaildPost++
						fmt.Println("Post Meta data")
						log.Fatal(err)
					}
				}
			}
		}
	} else {
		FaildPost++
		log.Println("Title & Content empty..")
	}
}

func metaStatement() *sql.Stmt {
	if stmtIns == nil {
		stmtIns, err = Db.Prepare("INSERT INTO wp_postmeta (post_id,meta_key,meta_value) VALUES(?,?,?)")
		if err != nil {
			fmt.Println("Meta In Insert")
			log.Fatal(err)
		}
	}
	return stmtIns
}
