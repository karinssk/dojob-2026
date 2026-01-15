<!-- Statistics Section with Toggle - Tailwind -->
<div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h5 class="flex items-center text-lg font-semibold text-gray-800">
            <i data-feather="bar-chart-2" class="w-5 h-5 mr-2 text-blue-600"></i>
            Project Statistics
        </h5>
        <button type="button" class="flex items-center space-x-2 px-3 py-1 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors duration-200" id="statsToggleBtn" onclick="toggleStats()">
            <i data-feather="eye-off" class="w-4 h-4" id="statsToggleIcon"></i>
            <span id="statsToggleText">Hide</span>
        </button>
    </div>
    
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4" id="statsContainer">
        <?php 
        $stats = array(
            'Draft' => 0, 'Editing' => 0, 'Review' => 0, 'Approved' => 0, 'Final' => 0
        );
        foreach ($statistics as $stat) {
            $stats[$stat->story_status] = $stat->total;
        }
        $total_shots = array_sum($stats);
        ?>
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-700 mb-1"><?php echo $total_shots; ?></div>
            <div class="text-sm text-blue-600 font-medium">Total Shots</div>
        </div>
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-700 mb-1"><?php echo $stats['Draft']; ?></div>
            <div class="text-sm text-gray-600 font-medium">Draft</div>
        </div>
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-yellow-700 mb-1"><?php echo $stats['Editing']; ?></div>
            <div class="text-sm text-yellow-600 font-medium">Editing</div>
        </div>
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-700 mb-1"><?php echo $stats['Review']; ?></div>
            <div class="text-sm text-blue-600 font-medium">Review</div>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-700 mb-1"><?php echo $stats['Approved']; ?></div>
            <div class="text-sm text-green-600 font-medium">Approved</div>
        </div>
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-purple-700 mb-1"><?php echo $stats['Final']; ?></div>
            <div class="text-sm text-purple-600 font-medium">Final</div>
        </div>
    </div>
</div>