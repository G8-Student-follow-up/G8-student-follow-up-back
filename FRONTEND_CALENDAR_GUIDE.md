# Calendar Feature — Frontend Integration Guide

## 1. API Endpoint

```
GET /api/calendar/events?month=7&year=2026
Authorization: Bearer {sanctum_token}
```

**Parameters:**

| Param | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `month` | int | No | Current month | 1–12 |
| `year` | int | No | Current year | e.g. 2026 |

If omitted, returns **all** card + student events plus the school timetable for the current month.

---

## 2. Response Structure

```json
{
  "events": [
    {
      "id": "1",
      "title": "Fix login bug",
      "date": "2026-07-15",
      "type": "Follow-up",
      "color": "#ef4444",
      "time": "All day",
      "source": "card"
    },
    {
      "id": "student-5",
      "title": "John Doe",
      "date": "2026-07-18",
      "type": "Student Follow-up",
      "color": "#10b981",
      "time": "All day",
      "source": "student"
    },
    {
      "id": "school-him-abc123",
      "title": "Tech Real World Project",
      "date": "2026-07-22",
      "type": "Training",
      "color": "#5C5CEE",
      "time": "07:00 - 13:00",
      "source": "school",
      "trainer": "Him",
      "class": "",
      "location": ""
    },
    {
      "id": "school-y2a-def456",
      "title": "Web Development",
      "date": "2026-07-22",
      "type": "Class",
      "color": "#4F46E5",
      "time": "13:00 - 17:00",
      "source": "school",
      "trainer": "",
      "class": "WEP/Y2-A",
      "location": "B12"
    }
  ]
}
```

---

## 3. Event Fields Reference

### All events share:

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique identifier (prefixed per source) |
| `title` | string | Event name / student name / card title |
| `date` | string | ISO date `Y-m-d` |
| `type` | string | `"Follow-up"`, `"Student Follow-up"`, `"Training"`, or `"Class"` |
| `color` | string | Hex `#rrggbb` for UI badges/dots |
| `time` | string | `"All day"` or `"HH:MM - HH:MM"` |
| `source` | string | `"card"`, `"student"`, or `"school"` |

### School events have extra fields:

| Field | Type | Description |
|-------|------|-------------|
| `trainer` | string | Trainer name (empty for classes) |
| `class` | string | Class name (empty for trainer events) |
| `location` | string | Room number e.g. `"B12"` |

---

## 4. Color Reference

| Source | Priority | Color | Hex |
|--------|----------|-------|-----|
| Card | High | Red | `#ef4444` |
| Card | Medium | Amber | `#f59e0b` |
| Card | Low/None | Blue | `#2563eb` |
| Student | High | Red | `#ef4444` |
| Student | Medium | Amber | `#f59e0b` |
| Student | Low/None | Green | `#10b981` |
| School (Trainer) | Per trainer | Various | `#5C5CEE`, `#10B981`, etc. |
| School (Class) | Per class | Various | `#4F46E5`, `#059669`, etc. |

---

## 5. Fetching Events (JavaScript)

```typescript
interface CalendarEvent {
  id: string;
  title: string;
  date: string;
  type: string;
  color: string;
  time: string;
  source: "card" | "student" | "school";
  trainer?: string;
  class?: string;
  location?: string;
}

async function fetchCalendarEvents(month?: number, year?: number) {
  const params = new URLSearchParams();
  if (month) params.set("month", String(month));
  if (year) params.set("year", String(year));

  const token = localStorage.getItem("sanctum_token");
  const res = await fetch(`/api/calendar/events?${params}`, {
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    },
  });

  if (!res.ok) throw new Error(`Calendar fetch failed: ${res.status}`);
  const data = await res.json();
  return data.events as CalendarEvent[];
}
```

---

## 6. Grouping by Day

```typescript
function groupByDay(events: CalendarEvent[]): Map<string, CalendarEvent[]> {
  const map = new Map<string, CalendarEvent[]>();
  for (const ev of events) {
    const arr = map.get(ev.date) ?? [];
    arr.push(ev);
    map.set(ev.date, arr);
  }
  // Sort within each day: all-day first, then by time
  for (const [, arr] of map) {
    arr.sort((a, b) => {
      if (a.time === "All day" && b.time === "All day") return 0;
      if (a.time === "All day") return -1;
      if (b.time === "All day") return 1;
      return a.time.localeCompare(b.time);
    });
  }
  return map;
}
```

---

## 7. Caching

- **Backend** caches school timetable for **3 hours**
- **First load**: ~5–6 seconds (18 calendar queries in parallel)
- **Subsequent loads**: Instant (from cache)
- **Cards & students**: Always fresh (from DB)

Frontend suggestion: cache the response in localStorage with a 5-minute TTL so navigating months is instant.

---

## 8. UI Layout Suggestion

```
┌─────────────────────────────────────┐
│  ← July 2026 →       [Legend]      │
│  Mo Tu We Th Fr Sa Su               │
│      1  2  3  4  5                  │
│       ●         ●                   │
│  6  7  8  9 10 11 12                │
│  ●                                  │
│ ...                                  │
├─────────────────────────────────────┤
│  Selected Day: July 22              │
│  ─────────────────────────────      │
│  🟣 07:00 Tech Real World Project  │
│     Him · B12                       │
│  🔵 13:00 Web Development          │
│     WEP/Y2-A · B12                  │
└─────────────────────────────────────┘
```

### Source Filter
Add a toggle bar so users can filter by source:
```
[✓] Cards  [✓] Students  [✓] School Timetable
```

### Empty State
```html
<div class="calendar-empty">
  <h3>No events this month</h3>
  <p>Create a card with a follow-up date to see it here.</p>
</div>
```

---

## 9. React Quick Start

```tsx
import { useState, useEffect } from "react";

export default function CalendarPage() {
  const today = new Date();
  const [month, setMonth] = useState(today.getMonth() + 1);
  const [year, setYear] = useState(today.getFullYear());
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    fetch(`/api/calendar/events?month=${month}&year=${year}`, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem("token")}`,
      },
    })
      .then((r) => r.json())
      .then((d) => setEvents(d.events))
      .finally(() => setLoading(false));
  }, [month, year]);

  const grouped = groupByDay(events);

  function prevMonth() {
    if (month === 1) { setMonth(12); setYear(year - 1); }
    else { setMonth(month - 1); }
  }

  function nextMonth() {
    if (month === 12) { setMonth(1); setYear(year + 1); }
    else { setMonth(month + 1); }
  }

  return (
    <div>
      <header style={{ display: "flex", gap: 16, alignItems: "center" }}>
        <button onClick={prevMonth}>←</button>
        <h2>{new Date(year, month - 1).toLocaleString("default", { month: "long", year: "numeric" })}</h2>
        <button onClick={nextMonth}>→</button>
      </header>

      {loading ? (
        <p>Loading calendar...</p>
      ) : events.length === 0 ? (
        <div className="calendar-empty">
          <h3>No events this month</h3>
        </div>
      ) : (
        <div>
          {Array.from(grouped.entries()).map(([date, dayEvents]) => (
            <div key={date}>
              <strong>{date}</strong>
              {dayEvents.map((ev) => (
                <div key={ev.id} style={{ borderLeft: `4px solid ${ev.color}`, paddingLeft: 8, margin: "4px 0" }}>
                  <span>{ev.time}</span>
                  <strong>{ev.title}</strong>
                  {ev.trainer && <span> · {ev.trainer}</span>}
                  {ev.class && <span> · {ev.class}</span>}
                  {ev.location && <span> · {ev.location}</span>}
                  <span style={{ color: ev.color }}> ● {ev.type}</span>
                </div>
              ))}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
```

---

## 10. Important Notes for Your Frontend

1. **IDs can be strings** — `id: "student-5"` or `id: "school-him-abc123"`. Use `ev.id` as the React key, not the index.
2. **Time is a display string** — `"07:00 - 13:00"` is pre-format
