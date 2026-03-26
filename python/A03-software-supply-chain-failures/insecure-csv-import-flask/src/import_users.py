import csv
from io import StringIO


class ImportValidationError(Exception):
    pass


class InvalidFileTypeError(ImportValidationError):
    pass


class InvalidCsvError(ImportValidationError):
    pass


def format_header_names(headers):
    return ["<empty>" if header == "" else header for header in headers]


def import_users_from_csv(file_bytes, filename, comments):
    # Enforce an early server-side import contract check on the uploaded filename.
    # This does not prove the file contents are really CSV, but it is still a useful
    # first validation step before deeper content and header checks.
    if not filename or not filename.lower().endswith(".csv"):
        raise InvalidFileTypeError("The uploaded file must be a CSV file")

    # Decode uploaded bytes into text; reject files that are not valid UTF-8 text
    try:
        csv_text = file_bytes.decode("utf-8")
    except UnicodeDecodeError:
        raise InvalidCsvError("Uploaded file must be a UTF-8 CSV text file")

    file_size_bytes = len(file_bytes)
    
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
        raise InvalidCsvError(
            "Invalid CSV headers. " 
            f"Missing: {sorted(missing_headers)}. "
            f"Unexpected: {sorted(format_header_names(unexpected_headers))}."
        )

    # Count rows in the CSV import stream
    imported_count = 0

    for row_number, row in enumerate(csv_dictreader_object, start=2):
        email = (row.get("email") or "").strip()
        name = (row.get("name") or "").strip()
        role = (row.get("role") or "").strip()
        
        if not email or not name or not role:
            raise InvalidCsvError(
                f"Invalid CSV row at row {row_number}. "
                "Each row must contain non-empty email, name and role values."
            )

        # Reject rows whose email field does not meet the basic import format rule
        if "@" not in email:
            raise InvalidCsvError(
                f"Invalid CSV row at row {row_number}. "
                "Please ensure the email address is valid."
            )

        imported_count += 1

    # return success result Dict to caller
    return {
        "message": "Import completed",
        "filename": filename,
        "comments": comments,
        "size_bytes": file_size_bytes,
        "imported_count": imported_count
    }