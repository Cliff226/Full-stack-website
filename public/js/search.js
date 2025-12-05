function showHint(str) {
    // Get the dropdown container
    const hintBox = document.getElementById("txtHint");

    // Handle empty input

    if (str.length === 0) {
        hintBox.innerHTML = "";
        hintBox.classList.add("hidden");
        return;
    }
    // Create an AJAX request
    const xhr = new XMLHttpRequest();
    // Wait for server response
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            let response = this.responseText;
            // Handle no suggestions
            if (response === "no suggestion") {
                hintBox.innerHTML = "";
                hintBox.classList.add("hidden");
                return;
            }

            //Parse server response by converting the comma list into array
            let suggestions = response.split(",").map(s => s.trim());

            // Build dropdown items
            hintBox.innerHTML = suggestions
                 // .map() → goes through each club name in the array and creates a <div> element for each.
                 //when user clicks on suggestion ('${name}') the function is called with that name
                .map(name => `<div class="suggestion-item" onclick="selectSuggestion('${name}')">${name}</div>`)
                // .join is used to turn the array of <div> strings into a single string to set as innerHTML.
                .join("");
            // Show the dropdown with clickable suggestions
            hintBox.classList.remove("hidden");
        }
    };
    // Send the AJAX request
    // encodeURIComponent ensures special characters in the search string are safely included
    xhr.open("GET", "/search.php?q=" + encodeURIComponent(str), true);
    //send a request to the server
    xhr.send();
}
// This function is called when a user clicks on one of the suggestions
function selectSuggestion(name) {
    // Finds the input field with id="searchInput"
    document.getElementById("searchInput").value = name;

    // Hide dropdown
    document.getElementById("txtHint").innerHTML = "";
    document.getElementById("txtHint").classList.add("hidden");
}
