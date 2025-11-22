function showHint(str) {
    const hintBox = document.getElementById("txtHint");

    if (str.length === 0) {
        hintBox.innerHTML = "";
        hintBox.classList.add("hidden");
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            let response = this.responseText;

            if (response === "no suggestion") {
                hintBox.innerHTML = "";
                hintBox.classList.add("hidden");
                return;
            }

            // Convert comma list into array
            let suggestions = response.split(",").map(s => s.trim());

            // Build dropdown items
            hintBox.innerHTML = suggestions
                .map(name => `<div class="suggestion-item" onclick="selectSuggestion('${name}')">${name}</div>`)
                .join("");

            hintBox.classList.remove("hidden");
        }
    };

    xhr.open("GET", "search.php?q=" + encodeURIComponent(str), true);
    xhr.send();
}

function selectSuggestion(name) {
    document.getElementById("searchInput").value = name;

    // Hide dropdown
    document.getElementById("txtHint").innerHTML = "";
    document.getElementById("txtHint").classList.add("hidden");
}
