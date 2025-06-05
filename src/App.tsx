import React, { useState, useEffect } from 'react';
import { Search, MapPin, Crown, Shield, Users } from 'lucide-react';

function App() {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [location, setLocation] = useState(null);

  useEffect(() => {
    // Simulated data for demonstration
    const mockUsers = [
      {
        id: 1,
        name: 'Alex',
        age: 28,
        distance: 0.5,
        isPremium: true,
        isVerified: true,
        isOnline: true,
        avatar: 'https://images.pexels.com/photos/614810/pexels-photo-614810.jpeg?auto=compress&cs=tinysrgb&w=200'
      },
      {
        id: 2,
        name: 'Sam',
        age: 32,
        distance: 1.2,
        isPremium: false,
        isVerified: true,
        isOnline: false,
        avatar: 'https://images.pexels.com/photos/1222271/pexels-photo-1222271.jpeg?auto=compress&cs=tinysrgb&w=200'
      },
      {
        id: 3,
        name: 'Jordan',
        age: 25,
        distance: 2.5,
        isPremium: true,
        isVerified: false,
        isOnline: true,
        avatar: 'https://images.pexels.com/photos/220453/pexels-photo-220453.jpeg?auto=compress&cs=tinysrgb&w=200'
      }
    ];

    setTimeout(() => {
      setUsers(mockUsers);
      setLoading(false);
    }, 1000);
  }, []);

  return (
    <div className="min-h-screen bg-gray-100">
      {/* Header */}
      <header className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex justify-between items-center">
            <h1 className="text-2xl font-bold text-gray-900">Users Nearby</h1>
            <div className="flex items-center gap-4">
              <button className="bg-[#00FFCC] text-black px-4 py-2 rounded-lg font-medium hover:bg-[#00e6b8] transition-colors">
                <Crown className="inline-block w-5 h-5 mr-2" />
                Go Premium
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Search and Filters */}
        <div className="bg-white rounded-lg shadow p-6 mb-8">
          <div className="flex flex-col md:flex-row gap-4">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search users..."
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#00FFCC] focus:border-transparent"
              />
            </div>
            <div className="flex gap-4">
              <select className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#00FFCC] focus:border-transparent">
                <option>Distance</option>
                <option>1km</option>
                <option>5km</option>
                <option>10km</option>
              </select>
              <select className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#00FFCC] focus:border-transparent">
                <option>Online</option>
                <option>All</option>
                <option>Premium</option>
                <option>Verified</option>
              </select>
            </div>
          </div>
        </div>

        {/* Users Grid */}
        {loading ? (
          <div className="flex justify-center items-center h-64">
            <div className="animate-spin rounded-full h-12 w-12 border-4 border-[#00FFCC] border-t-transparent"></div>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {users.map(user => (
              <div key={user.id} className="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                <div className="relative">
                  <img src={user.avatar} alt={user.name} className="w-full h-64 object-cover" />
                  <div className="absolute top-4 right-4 flex flex-col gap-2">
                    {user.isPremium && (
                      <span className="bg-yellow-400 text-black px-2 py-1 rounded-full text-sm font-medium">
                        <Crown className="inline-block w-4 h-4" />
                      </span>
                    )}
                    {user.isVerified && (
                      <span className="bg-blue-500 text-white px-2 py-1 rounded-full text-sm font-medium">
                        <Shield className="inline-block w-4 h-4" />
                      </span>
                    )}
                  </div>
                  <div className="absolute bottom-4 left-4">
                    <span className="bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm">
                      <MapPin className="inline-block w-4 h-4 mr-1" />
                      {user.distance}km
                    </span>
                  </div>
                  {user.isOnline && (
                    <div className="absolute top-4 left-4">
                      <span className="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                        Online
                      </span>
                    </div>
                  )}
                </div>
                <div className="p-4">
                  <div className="flex justify-between items-center mb-2">
                    <h3 className="text-lg font-semibold">{user.name}, {user.age}</h3>
                  </div>
                  <div className="flex gap-2">
                    <button className="flex-1 bg-[#00FFCC] text-black px-4 py-2 rounded-lg font-medium hover:bg-[#00e6b8] transition-colors">
                      Message
                    </button>
                    <button className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                      Profile
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </main>
    </div>
  );
}

export default App;