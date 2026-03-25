from flask import Flask, jsonify, request
import csv
from io import StringIO
from werkzeug.exceptions import RequestEntityTooLarge

app = Flask(__name__)

# Limit request size for upload safety; Flask will reject larger requests with 413
app.config["MAX_CONTENT_LENGTH"] = 1 * 1024 * 1024


@app.errorhandler(RequestEntityTooLarge)
def handle_file_too_large(error):
    return jsonify({
        "error": "File too large",
        "message": "Uploaded file must be smaller than 1 MiB"
    }), 413


@app.route("/")
def index():
    return jsonify({"message": "CSV import service"})


@app.route("/import-users", methods=["POST"])
def import_users():
    uploaded_file = request.files.get("file")    
    comments = request.form.get("comments")

    if comments is None:
        comments = ''

    if uploaded_file is None:
        return jsonify({"error": "CSV file is required"}), 400
    
    # Enforce an early server-side import contract check on the uploaded filename.
    # This does not prove the file contents are really CSV, but it is still a useful
    # first validation step before deeper content and header checks.
    if not uploaded_file.filename or not uploaded_file.filename.lower().endswith(".csv"):
        return jsonify({
            "error": "Invalid file type",
            "message": "The uploaded file must be a CSV file"
        }), 400

    # Read contents of file as Python bytes
    file_contents = uploaded_file.read()

    # Decode uploaded bytes into text; reject files that are not valid UTF-8 text
    try:
        csv_text = file_contents.decode("utf-8")
    except UnicodeDecodeError:
        return jsonify({
            "error": "Invalid file format",
            "message": "Uploaded file must be a UTF-8 CSV text file"
        }), 400

    file_size_bytes = len(file_contents)
    
    # Wrap the in-memory CSV text in a file-like object for the csv module
    csv_file = StringIO(csv_text)

    # Instantiate DictReader Object
    csv_dictreader_object = csv.DictReader(csv_file)

    # Define the CSV headers this import requires (set)
    header_contract = {"email", "name", "role"}

    # Get the headers detected by csv.DictReader from the first row (set)    
    actual_headers = set(csv_dictreader_object.fieldnames or [])

    # Reject the upload if headers do not match the expected contract exactly
    missing_headers = header_contract - actual_headers
    unexpected_headers = actual_headers - header_contract

    if missing_headers or unexpected_headers:
        return jsonify({
            "error": "Invalid CSV headers",
            "missing_headers": sorted(missing_headers),
            "unexpected_headers": sorted(unexpected_headers)
        }), 400

    # Count rows in the CSV import stream
    imported_count = 0

    for row_number, row in enumerate(csv_dictreader_object, start=2):
        email = (row.get("email") or "").strip()
        name = (row.get("name") or "").strip()
        role = (row.get("role") or "").strip()
        
        if not email or not name or not role:
            return jsonify({
                "error": "Invalid CSV row",
                "row_number": row_number,
                "message": "Each row must contain non-empty email, name and role values"
            }), 400

        # Reject rows whose email field does not meet the basic import format rule
        if "@" not in email:
            return jsonify({
                "error": "Invalid CSV row",
                "row_number": row_number,
                "message": "Please ensure the email address is valid"
            }), 400

        imported_count += 1

    # return JSON object to Response body
    return jsonify({
        "message": "Import completed",
        "filename": uploaded_file.filename,
        "comments": comments,
        "size_bytes": file_size_bytes,
        "imported_count": imported_count
    }), 200


if __name__ == "__main__":
    #app.run(debug=True)
    app.run()

