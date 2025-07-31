# Function-Based Formatting System

## Overview

The frontend has been upgraded from a **regex pattern matching** system to a **function-based routing** system for determining which card format to display for API responses.

## What Changed

### ❌ **Old System (Removed)**
```javascript
// FRAGILE: Multiple regex checks for each response
formatApiResponse(resp) {
  if (this.containsDiskUsage(resp)) return this.formatDiskUsage(resp);
  if (this.containsSystemLoad(resp)) return this.formatSystemLoad(resp);
  if (this.containsAccountsList(resp)) return this.formatAccountsList(resp);
  // ...many more checks
}

containsDiskUsage(resp) {
  return (resp.includes('Disk Blocks') && resp.includes('Inodes')) ||
         /\d+\s+Disk Blocks/i.test(resp) ||
         // ...fragile text pattern matching
}
```

### ✅ **New System (Implemented)**
```javascript
// RELIABLE: Direct function-to-formatter mapping
formatApiResponse(resp) {
  const functionName = this.extractFunctionName(); // Get from backend metadata
  
  if (functionName) {
    return this.formatByFunction(resp, functionName);
  }
  
  return this.formatAsBeautifulResponse(resp);
}

formatByFunction(resp, functionName) {
  const formatters = {
    'systemloadavg': () => this.formatLoadMetrics(resp),
    'get_disk_usage': () => this.formatDiskUsage(resp),
    'listaccts': () => this.formatAccountsList(resp),
    // ...direct mapping for all functions
  };
  
  return formatters[functionName] ? formatters[functionName]() : fallback;
}
```

## How It Works

### 1. **Backend Provides Function Metadata**
When a function executes, the backend creates structured responses:
```php
$chatHistory[] = [
    "role" => "assistant",
    "name" => "systemloadavg_call_response",  // ← Function identifier!
    "content" => json_encode($formattedData)
];
```

### 2. **Frontend Extracts Function Name**
```javascript
extractFunctionName() {
  // Extract from patterns like "systemloadavg_call_response"
  const match = lastResponse.name.match(/^(.+)_call_response$/);
  return match ? match[1] : null;
}
```

### 3. **Direct Routing to Appropriate Formatter**
```javascript
// No guessing needed - we KNOW what function was called
const formatter = formatters[functionName];
if (formatter) {
  return formatter(); // Direct route to appropriate card
}
```

## Supported Functions

### **System & Server Functions**
- `systemloadavg` → Load metrics cards
- `get_disk_usage` → Disk usage analysis
- `get_domain_info` → Domain information
- `get_information` → System information

### **Account Management**
- `listaccts` → Account listing table
- `createacct` → Account creation confirmation

### **Email Functions**
- `list_pops` → Email accounts list
- `count_pops` → Email count summary
- `add_pop` → Email account created
- `delete_pop` → Email account deleted

### **WHMCS Client Functions**
- `getClientDetails` → Client information
- `getClientsProducts` → Client products
- `getProducts` → Product information
- `getInvoices` → Invoices list

### **Special Functions**
- `RECOMMENDED TICKET RESPONSE` → Ticket response
- `agentChat` → Agent message
- `ticketResponse` → Ticket response

## Benefits

✅ **100% Reliable** - No guessing content type from text patterns
✅ **Future-proof** - New functions automatically supported  
✅ **Faster** - No regex processing overhead
✅ **Maintainable** - Clear function → formatter mapping
✅ **Debuggable** - Console logs show exact function routing

## Debugging

The system includes console logging:
```javascript
console.log('Extracted function name:', functionName);
console.log('Using function-based formatter for:', functionName);
```

Check browser console to verify function detection and routing.

## Fallback Behavior

- **Unknown functions**: Get generic beautiful card with auto-generated title
- **Non-function responses**: Use generic response formatter
- **Missing formatters**: Graceful fallback to default card

## Testing

1. Approve any function in the Action Queue
2. Check browser console for function detection logs
3. Verify response gets proper formatted card (not generic)
4. Test multiple function types to verify routing

## Migration Notes

- **Removed**: All `containsXxx()` methods (60+ lines of regex code)
- **Added**: Function extraction and direct routing (20 lines)
- **Maintained**: All existing formatter methods unchanged
- **Improved**: More robust and reliable card detection 