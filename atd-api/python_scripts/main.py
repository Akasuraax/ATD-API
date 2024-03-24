import sys
import json
from heapq import heappop, heappush

def prim(graph):
    mst = []
    visited = set()
    start_node = list(graph.keys())[0]
    visited.add(start_node)
    min_heap = [(weight, start_node, neighbor) for neighbor, weight in graph[start_node]]
    best_path = [start_node]

    while min_heap:
        weight, u, v = heappop(min_heap)
        if v not in visited:
            mst.append((u, v, weight))
            visited.add(v)
            best_path.append(v)
            for neighbor, weight in graph[v]:
                if neighbor not in visited:
                    heappush(min_heap, (weight, v, neighbor))

    return best_path

def main():
    json_data = sys.stdin.read()
    data = json.loads(json_data)
    minimum_spanning_tree = prim(data)
    print(minimum_spanning_tree)


main()
